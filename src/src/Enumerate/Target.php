<?php
declare(strict_types=1);

namespace Src\Enumerate;

use Composer\Util\Filesystem;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

enum Target
{
	case  BUILD_DIR;
	case  RELEASE_DIR;
	case  RUNTIME_DIR;

	public function path(): string
	{
		$path = match ($this) {
			self::BUILD_DIR   => 'target' . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR,
			self::RELEASE_DIR => 'target' . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR,
			self::RUNTIME_DIR => 'target' . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR,
		};

		return new Filesystem()->normalizePath(getcwd()) . DIRECTORY_SEPARATOR . $path;
	}

	public function readyDirectory(int $permissions = 0777): void
	{
		$directory = $this->path();

		if (!is_dir($directory)) {
			mkdir($directory, $permissions, true);
			return;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST,
		);

		foreach ($iterator as $fileInfo) {
			$fileInfo->isDir()
				? rmdir($fileInfo->getRealPath())
				: unlink($fileInfo->getRealPath());
		}
	}
}