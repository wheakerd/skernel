<?php
declare(strict_types=1);

namespace Src\Factory;

use Composer\Composer;
use Composer\IO\NullIO;
use SuperKernel\Attribute\Contract;
use SuperKernel\Attribute\Factory;

#[
	Contract(Composer::class),
	Factory,
]
final class ComposerFactory
{
	public function __invoke(): Composer
	{
		return \Composer\Factory::create(new NullIO);
	}
}