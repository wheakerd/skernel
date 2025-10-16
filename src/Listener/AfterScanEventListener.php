<?php
declare(strict_types=1);

namespace Src\Listener;

use Composer\ClassMapGenerator\ClassMapGenerator;
use Composer\Composer;
use Composer\Util\Filesystem;
use Phar;
use PhpParser\BuilderFactory;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\ClosureUse;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\DeclareDeclare;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;
use PhpParser\PrettyPrinter\Standard;
use Src\Cache\ClassnameCache;
use Src\Cache\PackageCache;
use Src\Event\AfterScanEvent;
use Src\Provider\ConfigProvider;
use SuperKernel\Attribute\Listener;
use SuperKernel\Contract\ListenerInterface;

#[Listener(AfterScanEvent::class, 2)]
final readonly class AfterScanEventListener implements ListenerInterface
{
	public function __construct(
		private Composer       $composer,
		private ClassnameCache $classnameCache,
		private PackageCache   $packageCache,
		private ConfigProvider $configProvider,
	)
	{
	}

	public function process(object $event): void
	{
		$annotations = array_merge_recursive($this->classnameCache->getAttributes(),
		                                     $this->packageCache->getAttributes());

		$phar = new Phar($this->configProvider->pharFilename);
		$phar->startBuffering();
		$phar->setStub($this->getDefaultStub($annotations));

		$phar->buildFromDirectory($this->configProvider->runtimeFolder);

		$phar->compressFiles(Phar::GZ);

		$phar->stopBuffering();
	}

	private function getDefaultStub(array $annotations): string
	{
		$classmap = ClassMapGenerator::createMap($this->configProvider->runtimeFolder);

		$factory = new BuilderFactory();
		$stmts   = [];

		// add <?php declare(strict_types=1);
		$stmts[] = new Declare_(
			[
				new DeclareDeclare(
					'strict_types',
					new  LNumber(1),
				),
			],
		);

		$stmts[] = new Expression(
			new StaticCall(
				new Name('Phar'), // 类名 Phar
				'mapPhar',        // 方法名 mapPhar
				[new Arg(new String_($this->configProvider->pharName . '.phar'))], // 参数 'skernel.phar'
			),
		);

		// Construct functions file loading
		$files = $this->getAllFilesFromPackages();

		foreach ($files as $file) {
			$stmts[] = new Expression(
				new FuncCall(
					new Name('require_once'),
					[
						new Arg(
							new String_('phar://' . $this->configProvider->pharName . '.phar/' . $file),
						),
					],
				),
			);
		}

		// Construct $classmap = [...]
		$items = [];

		foreach ($classmap as $class => $path) {
			$relative = ltrim(str_replace($this->configProvider->runtimeFolder, '', new Filesystem()->normalizePath($path)), '/\\');

			$items[] = new ArrayItem(
				new String_('phar://' . $this->configProvider->pharName . '.phar/' . $relative),
				new ClassConstFetch(new Name($class), 'class'),
			);
		}
		$stmts[] = new Expression(
			new Assign(
				$factory->var('classmap'),
				new Array_($items, ['kind' => Array_::KIND_SHORT]),
			),
		);

		// Define global variables
		$arrayItems = [];

		// Iterate over each annotation and create an ArrayItem with the key value for it
		foreach ($annotations as $key => $annotation) {
			$subArrayItems = [];
			foreach ($annotation as $item) {
				$subArrayItems[] = new ArrayItem(
					new ClassConstFetch(new Name('\\' . $item), 'class'), // 值
				);
			}
			// Use the original key value as the key for each subarray
			$arrayItems[] = new ArrayItem(
				new Array_($subArrayItems),
				new ClassConstFetch(new Name($key), 'class'),
			);
		}

		$attributes = new Array_($arrayItems);

		// Building spl_autoload_register
		$closure = new Closure(
			[
				'params' => [new Param(new Variable('class'))],
				'uses'   => [new ClosureUse(new Variable('classmap'))],
				'stmts'  => [
					new If_(
						new FuncCall(
							new Name('isset'),
							[
								new Arg(
									new ArrayDimFetch(
										new Variable('classmap'),
										new Variable('class'),
									),
								),
							],
						),
						[
							'stmts' => [
								new Expression(
									new Include_(
										new ArrayDimFetch(
											new Variable('classmap'),
											new Variable('class'),
										),
										Include_::TYPE_REQUIRE,
									),
								),
							],
						],
					),
				],
			],
		);

		$stmts[] = new Expression(
			new FuncCall(
				new Name('spl_autoload_register'),
				[
					new Arg($closure),
					new Arg(new ConstFetch(new Name('true'))),
				],
			),
		);

		// Add framework operation logic
		$stmts[] = new Expression(
			new MethodCall(
				new MethodCall(
					new New_(
						new Name('\SuperKernel\Di\Container'),
						[
							new Arg($attributes),
						],
					),
					new Identifier('get'),
					[
						new Arg(
							new ClassConstFetch(
								new Name('\SuperKernel\Contract\ApplicationInterface'),
								'class',
							),
						),
					],
				),
				new Identifier('run'),
			),
		);

		$stmts [] = new Expression(new FuncCall(new Name('__HALT_COMPILER'), []));

		// Convert AST syntax to PHP code
		$printer = new Standard();
		return $printer->prettyPrintFile($stmts);
	}

	private function getAllFilesFromPackages(): array
	{
		$files = [];

		// Get all packages
		$packages = $this->composer->getLocker()->getLockedRepository()->getPackages();

		// Traverse all packages
		foreach ($packages as $package) {
			// Get the autoload configuration for each package
			$autoload = $package->getAutoload();

			// Check the files configuration
			if (isset($autoload['files'])) {
				// Add to files array
				foreach ($autoload['files'] as $file) {
					$files[] = 'vendor/' . $package->getName() . '/' . $file;
				}
			}
		}

		return $files;
	}
}