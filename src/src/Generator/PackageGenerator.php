<?php
declare(strict_types=1);

namespace Src\Generator;

use AppendIterator;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Src\Abstract\GeneratorAbstract;
use Src\Contract\GeneratorInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class PackageGenerator extends GeneratorAbstract implements GeneratorInterface
{
	public function generate(OutputInterface $output): array
	{
		$this->iterator = new AppendIterator();

		$output->writeln('<info>[DEBUG]</info> Scanning packages...');

		$packages = $this->composer->getLocker()->getLockedRepository()->getPackages();

		$installationManager = $this->composer->getInstallationManager();

		$this->iterator = new AppendIterator();

		foreach ($packages as $package) {
			if ($package->getType() !== 'library') {
				continue;
			}

			$output->writeln("<info>[HIT]</info> 📦 {$package->getName()} ({$package->getPrettyVersion()})", OutputInterface::VERBOSITY_DEBUG);

			$installPath = $installationManager->getInstallPath($package);

			$this->iterator->append(
				new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator($installPath, FilesystemIterator::SKIP_DOTS),
				),
			);
		}

		$output->writeln('<info>[INFO] </info>Scanning the source code of the packages...', OutputInterface::VERBOSITY_DEBUG);

		return parent::generate($output);
	}
}