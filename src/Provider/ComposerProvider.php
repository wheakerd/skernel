<?php
declare(strict_types=1);

namespace Src\Provider;

use Composer\Composer;
use Composer\IO\NullIO;
use SuperKernel\Attribute\Contract;
use SuperKernel\Attribute\Factory;

#[
	Contract(Composer::class),
	Factory,
]
final class ComposerProvider
{
	public function __invoke(): Composer
	{
		return \Composer\Factory::create(new NullIO);
	}
}