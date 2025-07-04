<?php
declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class Version
{
	public static function semVer(string $version, bool $regulation = true): string
	{
		// Use regular matching SemVer style version numbers (main. Times. Patch).
		if (!preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $version, $matches)) {
			throw new InvalidArgumentException("Illegal version number format：$version");
		}

		[
			$full,
			$major,
			$minor,
			$patch,
		] = $matches;

		$major = (int)$major;
		$minor = (int)$minor;
		$patch = (int)$patch;

		// Decide whether to increment or decrement the patch number based on the regulation parameter.
		if ($regulation) {
			$patch++;
		} else {
			$patch = max(0, $patch - 1); // The patch number cannot be negative.
		}

		// 返回格式化的新版本号
		return sprintf('%d.%d.%d', $major, $minor, $patch);
	}
}