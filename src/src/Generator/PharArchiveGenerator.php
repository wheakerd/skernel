<?php
declare(strict_types=1);

namespace Src\Generator;

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
use PhpParser\Node\Expr\BinaryOp\Concat;
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
use PhpParser\Node\Scalar\MagicConst\Dir;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use PhpParser\PrettyPrinter\Standard;
use Src\Enumerate\Target;

final class PharArchiveGenerator
{
	private ?string $pharName = null {
		get => $this->pharName ??= $this->composer->getPackage()->getExtra()['name'] ?? 'bin' . '.phar';
	}

	public function __construct(private readonly Composer $composer)
	{
		Target::RELEASE_DIR->readyDirectory();
	}

	private function getPharFile(): string
	{
		$pharFile = Target::RELEASE_DIR->path() . $this->pharName . '.phar';

		if (file_exists($pharFile)) {
			unlink($pharFile);
		}

		return $pharFile;
	}

	public function generate(array $annotations): void
	{
		$phar = new Phar($this->getPharFile());
		$phar->startBuffering();
		$phar->setStub($this->getDefaultStub($annotations));

		$phar->buildFromDirectory(Target::RUNTIME_DIR->path());

		$phar->compressFiles(Phar::GZ);

		$phar->stopBuffering();
	}

	private function getDefaultStub(array $annotations): string
	{
		$classmap = ClassMapGenerator::createMap(Target::RUNTIME_DIR->path());

		$factory = new BuilderFactory();
		$stmts   = [];

		// 添加 <?php declare(strict_types=1);
		$stmts[] = new Stmt\Declare_(
			[
				new Stmt\DeclareDeclare(
					'strict_types',
					new  LNumber(1),
				),
			],
		);

		$stmts[] = new Stmt\Expression(
			new StaticCall(
				new Name('Phar'), // 类名 Phar
				'mapPhar',        // 方法名 mapPhar
				[new Arg(new String_($this->pharName . '.phar'))], // 参数 'skernel.phar'
			),
		);

		// 构造 functions 文件载入
		$files = $this->getAllFilesFromPackages();

		foreach ($files as $file) {
			$stmts[] = new Expression(
				new FuncCall(
					new Name('require_once'),
					[
						new Arg(
							new String_('phar://' . $this->pharName . '.phar/' . $file),
						),
					],
				),
			);
		}

		// 构建 $classmap = [...]
		$items = [];

		foreach ($classmap as $class => $path) {
			$relative = ltrim(str_replace(Target::RUNTIME_DIR->path(), '', new Filesystem()->normalizePath($path)), '/\\');

			$items[] = new ArrayItem(
				new String_('phar://' . $this->pharName . '.phar/' . $relative),
				new ClassConstFetch(new Name($class), 'class'),
			);
		}
		$stmts[] = new Expression(
			new Assign(
				$factory->var('classmap'),
				new Array_($items, ['kind' => Array_::KIND_SHORT]),
			),
		);

		// 定义 define 全局变量
		$arrayItems = [];

		// 遍历每个注解并为其创建具有键值的 ArrayItem
		foreach ($annotations as $key => $annotation) {
			$subArrayItems = [];
			foreach ($annotation as $item) {
				$subArrayItems[] = new ArrayItem(
					new ClassConstFetch(new Name('\\' . $item), 'class'), // 值
				);
			}
			// 使用原始键值作为每个子数组的键
			$arrayItems[] = new ArrayItem(
				new Array_($subArrayItems),
				new ClassConstFetch(new Name($key), 'class'),
			);
		}

		$attributes = new Array_($arrayItems);

		// 构建 spl_autoload_register
		$closure = new Closure(
			[
				'params' => [new Param(new Variable('class'))],
				'uses'   => [new ClosureUse(new Variable('classmap'))],
				'stmts'  => [
					new Stmt\If_(
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

		// 添加框架运行逻辑
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

		$stmts [] = new Expression(new FuncCall(new Name('__HALT_COMPILER'), []));;

		// 将 AST 语法转换成 PHP code
		$printer = new Standard();
		return $printer->prettyPrintFile($stmts);
	}

	/**
	 * 获取所有已安装包的 autoload.files 配置
	 *
	 * @return array 包含文件路径的数组
	 */
	private function getAllFilesFromPackages(): array
	{
		$files = [];

		// 获取所有包
		$packages = $this->composer->getLocker()->getLockedRepository()->getPackages();

		// 遍历所有包
		foreach ($packages as $package) {
			// 获取每个包的 autoload 配置
			$autoload = $package->getAutoload();

			// 检查 files 配置
			if (isset($autoload['files'])) {
				// 添加到文件数组
				foreach ($autoload['files'] as $file) {
					$files[] = 'vendor/' . $package->getName() . '/' . $file;
				}
			}
		}

		return $files;
	}
}