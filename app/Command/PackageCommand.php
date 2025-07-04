<?php
declare(strict_types=1);

namespace App\Command;

use App\Contract\ConfigProviderInterface;
use App\Parser\ClassNameResolver;
use App\Service\Phar\ClassMapAutoload;
use Composer\ClassMapGenerator\ClassMapGenerator;
use Composer\Factory;
use Composer\IO\NullIO;
use FilesystemIterator;
use Hyperf\Command\Annotation\Command as AsCommand;
use Hyperf\Command\Command as HyperfCommand;
use Phar;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Seld\JsonLint\ParsingException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Throwable;
use function getcwd;

#[AsCommand(name: 'package')]
final class PackageCommand extends HyperfCommand
{
	private Parser $parser;
//
//	private $composer;

	public function __construct(
//		private readonly ConfigProviderInterface $configProvider,
//		NullIO        $nullIO,
//		Factory       $factory,
		ParserFactory                   $parserFactory,
		private ClassMapAutoload        $classMapAutoload,
		private ConfigProviderInterface $configProvider,
	)
	{
		parent::__construct();

//		$this->composer = $factory->createComposer($nullIO);
		$this->parser = $parserFactory->createForHostVersion();
	}

	public function configure(): void
	{
		$this->setDescription('Pack your project into a Phar package.')
			->addOption('name', null, InputOption::VALUE_REQUIRED, 'Build the package name as phar.', 'run')
			->addOption('dev', null, InputOption::VALUE_REQUIRED, 'Whether the development kit needs to be included.', false);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		if (!$input->getOption('name')) {
			$output->writeln('<error>Option --name is required.</error>');
			return Command::FAILURE;
		}

		$cwd = getcwd();

		if ($cwd === false) {
			$output->writeln('<error>Failed to get current working directory. It may have been deleted or permission denied.</error>');
			return Command::FAILURE;
		}

		try {
			$this->handle();
		}
		catch (Throwable $throwable) {
			$output->writeln($throwable->getMessage());
			return Command::FAILURE;
		}

		return Command::SUCCESS;
	}

	/**
	 * @return void
	 * @throws ParsingException
	 */
	public function handle(): void
	{
		$name = $this->input->getOption('name');

		$this->classMapAutoload->setDevMode((boolean)$this->input->getOption('dev'));
		$this->output->info('Creating phar <info>' . $name . '</info>');

		$startTime   = microtime(true);
		$stub        = $this->classMapAutoload->getStub();
		$projectPath = $this->configProvider->getRootPath();
		$targetPath  = $projectPath . DIRECTORY_SEPARATOR . 'target';
		$pharName    = $name . '.phar';

		if (!is_dir($targetPath)) {
			mkdir($targetPath, 0777, true);
		}

		$pharFile = $targetPath . DIRECTORY_SEPARATOR . $name . '.phar';
		$phar     = new Phar($pharFile, 0, $pharName);

		$phar->setStub($stub);

		// 要打包的目录列表
		$includeDirs = [
			'app',
			'config',
			'runtime',
			'vendor',
		];

		foreach ($includeDirs as $dir) {
			$fullPath = $projectPath . '/' . $dir;

			if (!is_dir($fullPath)) {
				echo "Warning: directory '$dir' does not exist, skipping...\n";
				continue;
			}

			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($fullPath, FilesystemIterator::SKIP_DOTS),
				RecursiveIteratorIterator::LEAVES_ONLY,
			);

			foreach ($iterator as $file) {
				$localPath = str_replace($projectPath . '/', '', $file->getRealPath());
				$phar->addFile($file->getRealPath(), $localPath);
			}
		}

		$phar->compress(Phar::GZ);

		$phar->startBuffering();
	}
}