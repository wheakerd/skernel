<?php
declare(strict_types=1);

namespace Src\Listener;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Src\Event\AfterScanEvent;
use Src\Event\PharArchiveEvent;
use Src\Event\ScanEvent;
use SuperKernel\Attribute\Listener;
use SuperKernel\Contract\ListenerInterface;

#[Listener(PharArchiveEvent::class)]
final class PharArchiveEventListener implements ListenerInterface
{
	private ?EventDispatcherInterface $eventDispatcher = null {
		get => $this->eventDispatcher ??= $this->container->get(EventDispatcherInterface::class);
	}

	public function __construct(
		private readonly ContainerInterface $container,
	)
	{
	}

	/**
	 * @param object                 $event
	 *
	 * @psalm-param PharArchiveEvent $event
	 *
	 * @return void
	 */
	public function process(object $event): void
	{
		$output = $event->output;

		$output->writeln('<info>Building phar archive...</info>');

		$this->eventDispatcher->dispatch(new ScanEvent($output, $event->isProduction));
		
		$this->eventDispatcher->dispatch(new AfterScanEvent());

		$output->writeln('<info>PHAR archive created!</info>');
	}
}