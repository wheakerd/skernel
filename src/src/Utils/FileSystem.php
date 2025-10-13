<?php
declare(strict_types=1);

namespace Src\Utils;

use function getcwd;

final class FileSystem
{
	public function __construct()
	{
	}

	public function getPath()
	{
		return getcwd();
	}
}