<?php
declare(strict_types=1);

namespace App\Support;

use RuntimeException;
use function getcwd;

final class Directory
{
	/**
	 * @return string
	 */
	public static function getcwd(): string
	{
		$cwd = getcwd();
		if ($cwd === false) {
			throw new RuntimeException("Failed to get current working directory. It may have been deleted or permission denied.");
		}

		return $cwd;
	}
}