<?php
declare(strict_types=1);

namespace Src\Phar;

use Composer\Composer;
use Src\Utils\Factory;

final class PharBuilder
{
	private Composer $composer;

	public function __construct(Factory $composerFactory)
	{
		$this->composer = $composerFactory->getComposer();
	}

	public function handle(): void
	{
		$name = $this->composer->getPackage()->getExtra()['name'] ?? 'bin' . '.phar';

		var_dump($name);
	}
}