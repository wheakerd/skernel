<?php
declare(strict_types=1);

namespace Src\Provider;

use Composer\Composer;
use Composer\Semver\Semver;
use DirectoryIterator;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use function posix_getpwuid;
use function posix_getuid;

final class ConfigProvider
{
	private(set) ?string $homeFolder = null {
		get => $this->homeFolder ??= dirname($this->composer->getConfig()->get('vendor-dir')) . DIRECTORY_SEPARATOR;
	}

	private(set) ?string $targetFolder = null {
		get => $this->targetFolder ??= $this->homeFolder . 'target' . DIRECTORY_SEPARATOR;
	}

	private(set) ?string $cacheFolder = null {
		get => $this->cacheFolder ??= $this->targetFolder . 'cache' . DIRECTORY_SEPARATOR;
	}

	private(set) ?string $releaseFolder = null {
		get => $this->releaseFolder ??= $this->targetFolder . 'release' . DIRECTORY_SEPARATOR;
	}

	private(set) ?string $runtimeFolder = null {
		get => $this->runtimeFolder ??= $this->targetFolder . 'runtime' . DIRECTORY_SEPARATOR;
	}

	private(set) ?string $microSfxFolder = null {
		get => $this->microSfxFolder ??= posix_getpwuid(posix_getuid())['dir'] . DIRECTORY_SEPARATOR . '.skernel' . DIRECTORY_SEPARATOR;
	}

	private(set) ?string $name = null {
		get => $this->name ??= $this->composer->getPackage()->getExtra()['skernel']['name'] ?? 'bin';
	}

	private(set) ?string $pharName = null {
		get => $this->pharName ??= $this->name . '.phar';
	}

	private(set) ?string $binaryFilename = null {
		get => $this->binaryFilename ??= $this->targetFolder . 'release' . DIRECTORY_SEPARATOR . $this->name;
	}

	private(set) ?string $pharFilename = null {
		get {
			if (null === $this->pharFilename) {
				$this->pharFilename = $this->releaseFolder . $this->pharName;

				if ($this->filesystem->exists($this->pharFilename)) {
					$this->filesystem->remove($this->pharFilename);
				}
			}

			return $this->pharFilename;
		}
	}

	public function __construct(
		private readonly Composer   $composer,
		private readonly Filesystem $filesystem,
	)
	{
	}

	public function getMicroSfx(): ?string
	{
		$requires = $this->composer->getPackage()->getRequires();

		if (!isset($requires['php'])) {
			throw new RuntimeException('PHP version require not configured !');
		}

		$phpConstraint = $requires['php']->getConstraint()->getPrettyString();

		$directoryIterator = new DirectoryIterator($this->microSfxFolder);

		/* @var SplFileInfo $fileInfo */
		foreach ($directoryIterator as $fileInfo) {
			if (!$fileInfo->isDir()) continue;

			$pathname = $fileInfo->getBasename();

			$version = null;

			if (preg_match('/php-(\d+\.\d+\.\d+)-micro-[\w-]+$/', $pathname, $matches)) {
				$version = $matches[1];
			}

			if (null === $version) {
				continue;
			}

			if (Semver::satisfies($version, $phpConstraint)) {
				return $fileInfo->getRealPath();
			}
		}

		return null;
	}
}