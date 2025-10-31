<?php
declare(strict_types=1);

namespace Src\Listener;

use Src\Cache\ClassnameCache;
use Src\Cache\PackageCache;
use Src\Event\AfterScanEvent;
use SuperKernel\Attribute\Listener;
use SuperKernel\Contract\ListenerInterface;

#[Listener(AfterScanEvent::class)]
final readonly class WriteCacheHandler implements ListenerInterface
{
	public function __construct(
		private ClassnameCache $classnameCache,
		private PackageCache   $packageCache,
	)
	{
	}

	public function process(object $event): void
	{
		$this->classnameCache->writeCache();
		$this->packageCache->writeCache();
	}
}