<?php
declare(strict_types=1);

namespace Src\Listener;

use AppendIterator;
use CallbackFilterIterator;
use Composer\Composer;
use Composer\Factory;
use Composer\IO\NullIO;
use FilesystemIterator;
use IteratorIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Src\Ast\Scanner;
use Src\Cache\PackageCache;
use Src\Event\ScanEvent;
use Src\Provider\ConfigProvider;
use SuperKernel\Attribute\Listener;
use SuperKernel\Contract\ListenerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;
use function count;
use function iterator_to_array;

#[Listener(ScanEvent::class)]
final readonly class ScanEventListener implements ListenerInterface
{
	public function __construct(
		private Scanner        $scanner,
		private Composer       $composer,
		private Filesystem     $filesystem,
		private PackageCache   $packageCache,
		private ConfigProvider $configProvider,
	)
	{
	}

	/**
	 * @param object          $event
	 *
	 * @psalm-param ScanEvent $event
	 *
	 * @return void
	 */
	public function process(object $event): void
	{
		$output = $event->output;

		$output->writeln('<info>[INFO]</info> Scanning packages...');

		$runtimeDir          = $this->configProvider->runtimeFolder;
		$lockedRepository    = $this->composer->getLocker()->getLockedRepository($event->requireDev);
		$installationManager = $this->composer->getInstallationManager();

		$runtimeComposerJsonFile = $runtimeDir . 'composer.json';

		if ($this->filesystem->exists($runtimeComposerJsonFile)) {
			$runtimeComposer            = Factory::create(new NullIO, $runtimeComposerJsonFile);
			$runtimeLockedRepository    = $runtimeComposer->getLocker()->getLockedRepository($event->requireDev);
			$runtimeInstallationManager = $runtimeComposer->getInstallationManager();

			foreach ($lockedRepository->getPackages() as $package) {
				if ($package->getType() !== 'library') {
					continue;
				}

				$output->writeln("<info>[HIT]</info> 📦 {$package->getName()} ({$package->getPrettyVersion()})", OutputInterface::VERBOSITY_DEBUG);

				if (null === $runtimeLockedRepository->findPackage($package->getName(), $package->getVersion())) {

					$installPath = $installationManager->getInstallPath($package);

					$iterator = new RecursiveIteratorIterator(
						new RecursiveDirectoryIterator($installPath, FilesystemIterator::SKIP_DOTS),
					);

					foreach ($iterator as $file) {
						try {
							$this->scanner->scan($file, $event->requireDev, $package->getName());
						}
						catch (Throwable $throwable) {
							$output->writeln("<error>[ERROR]</error> {$throwable->getMessage()}");
						}
					}

					continue;
				}

				$this->packageCache->reuse($package->getName());

				$lockedRepository->removePackage($package);
				$runtimeLockedRepository->removePackage($package);
			}

			foreach ($runtimeLockedRepository->getPackages() as $package) {
				$installPath = $runtimeInstallationManager->getInstallPath($package);

				$this->filesystem->remove($installPath);
			}
		} else {
			foreach ($lockedRepository->getPackages() as $package) {
				if ($package->getType() !== 'library') {
					continue;
				}

				$output->writeln("<info>[HIT]</info> 📦 {$package->getName()} ({$package->getPrettyVersion()})", OutputInterface::VERBOSITY_DEBUG);

				$installPath = $installationManager->getInstallPath($package);

				$iterator = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator($installPath, FilesystemIterator::SKIP_DOTS),
				);

				foreach ($iterator as $file) {
					try {
						$this->scanner->scan($file, $event->requireDev, $package->getName());
					}
					catch (Throwable $throwable) {
						$output->writeln("<error>[ERROR]</error> {$throwable->getMessage()}");
					}
				}
			}
		}

		$output->writeln('<info>[INFO]</info> Scanning source code...');

		$iterator = new AppendIterator();

		$psr4Dirs = array_values($this->composer->getPackage()->getAutoload()['psr-4'] ?? []);

		if ($event->requireDev) {
			$psr4Dirs += array_values($this->composer->getPackage()->getDevAutoload()['psr-4'] ?? []);
		}

		// Add files under the project root directory (only one layer)
		$fs            = new FilesystemIterator($this->configProvider->homeFolder, FilesystemIterator::SKIP_DOTS);
		$rootFilesOnly = new CallbackFilterIterator($fs, fn(SplFileInfo $current): bool => $current->isFile());

		$iterator->append(new IteratorIterator($rootFilesOnly));

		foreach ($psr4Dirs as $psr4Dir) {
			$path = rtrim($this->configProvider->homeFolder . $psr4Dir, DIRECTORY_SEPARATOR);

			if (!is_dir($path)) {
				continue;
			}

			$recursive = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
			);

			$iterator->append($recursive);
		}

		$steps       = count(iterator_to_array($iterator));
		$progressBar = new ProgressBar($output, $steps);

		$progressBar->setFormat('[%bar%] %percent%% %elapsed:10s%');
		$progressBar->start();

		/* @var SplFileInfo $file */
		foreach ($iterator as $file) {
			$progressBar->advance();

			try {
				$this->scanner->scan($file, $event->requireDev);
			}
			catch (Throwable $throwable) {
				$output->writeln("<error>[ERROR]</error> {$throwable->getMessage()}");
			}
		}

		$progressBar->finish();

		$output->writeln('');
	}
}