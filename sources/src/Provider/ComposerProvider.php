<?php
declare(strict_types=1);

namespace Src\Provider;

use Composer\Composer;
use Composer\IO\NullIO;
use SuperKernel\Attribute\Factory;
use SuperKernel\Attribute\Provider;

#[
	Provider(Composer::class),
	Factory,
]
final class ComposerProvider
{
	public function __invoke(): Composer
	{
		return \Composer\Factory::create(new NullIO);
	}
}