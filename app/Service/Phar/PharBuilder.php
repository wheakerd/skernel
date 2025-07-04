<?php
declare(strict_types=1);

namespace App\Service\Phar;

use App\Support\Directory;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

final class PharBuilder
{
	public function __construct(string $name, private OutputInterface $output)
	{
	}

	public function build(): void
	{
	}

	public function getTargetPath(): string
	{
		$cwd = Directory::getcwd();

		$targetDir = rtrim($cwd, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'target';

		if (is_dir($targetDir)) {
			throw new RuntimeException("Directory already exists, please delete it manually first: $targetDir.");
		}

		if (file_exists($targetDir) && !is_dir($targetDir)) {
			throw new RuntimeException("Path exists and is not a directory: $targetDir.");
		}

		if (!mkdir($targetDir, 0755, true)) {
			throw new RuntimeException("Failed to create directory: $targetDir, please check permissions.");
		}

		return $targetDir;
	}
}