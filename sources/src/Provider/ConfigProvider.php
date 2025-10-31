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
		get {
			if (null === $this->targetFolder) {
				$folder = $this->homeFolder . 'target' . DIRECTORY_SEPARATOR;

				if (!$this->filesystem->exists($folder)) {
					$this->filesystem->mkdir($folder);
				}

				$this->targetFolder = $folder;
			}

			return $this->targetFolder;
		}
	}

	private(set) ?string $cacheFolder = null {
		get {
			if (null === $this->cacheFolder) {
				$folder = $this->targetFolder . 'cache' . DIRECTORY_SEPARATOR;

				if (!$this->filesystem->exists($folder)) {
					$this->filesystem->mkdir($folder);
				}

				$this->cacheFolder = $folder;
			}

			return $this->cacheFolder;
		}
	}

	private(set) ?string $releaseFolder = null {
		get {
			if (null === $this->releaseFolder) {
				$folder = $this->targetFolder . 'release' . DIRECTORY_SEPARATOR;

				if (!$this->filesystem->exists($folder)) {
					$this->filesystem->mkdir($folder);
				}

				$this->releaseFolder = $folder;
			}

			return $this->releaseFolder;
		}
	}

	private(set) ?string $runtimeFolder = null {
		get {
			if (null === $this->runtimeFolder) {
				$folder = $this->targetFolder . 'runtime' . DIRECTORY_SEPARATOR;

				if (!$this->filesystem->exists($folder)) {
					$this->filesystem->mkdir($folder);
				}

				$this->runtimeFolder = $folder;
			}

			return $this->runtimeFolder;
		}
	}

	private(set) ?string $microSfxFolder = null {
		get {
			if (null === $this->microSfxFolder) {
				$folder = posix_getpwuid(posix_getuid())['dir'] . DIRECTORY_SEPARATOR . '.skernel' . DIRECTORY_SEPARATOR;

				if (!$this->filesystem->exists($folder)) {
					$this->filesystem->mkdir($folder);
				}

				$this->microSfxFolder = $folder;
			}

			return $this->microSfxFolder;
		}
	}

	private(set) ?string $name = null {
		get => $this->name ??= $this->composer->getPackage()->getExtra()['skernel']['name'] ?? 'bin';
	}

	private(set) ?string $pharName = null {
		get => $this->pharName ??= $this->name . '.phar';
	}

	private(set) ?string $binaryFilename = null {
		get => $this->binaryFilename ??= $this->releaseFolder . $this->name;
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