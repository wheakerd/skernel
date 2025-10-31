<?php
declare(strict_types=1);

namespace Src;

use Psr\EventDispatcher\EventDispatcherInterface;
use SuperKernel\Attribute\Provider;
use SuperKernel\Contract\ApplicationInterface;
use SuperKernel\Event\BootApplication;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[Provider(ApplicationInterface::class)]
final class Application extends SymfonyApplication implements ApplicationInterface
{
	private string $logo = <<<LOGO
      ___           ___           ___           ___           ___           ___           ___ 
     /  /\         /  /\         /  /\         /  /\         /  /\         /  /\         /  /\
    /  /::\       /  /:/        /  /::\       /  /::\       /  /::|       /  /::\       /  /:/
   /__/:/\:\     /  /:/        /  /:/\:\     /  /:/\:\     /  /:|:|      /  /:/\:\     /  /:/ 
  _\_ \:\ \:\   /  /::\____   /  /::\ \:\   /  /::\ \:\   /  /:/|:|__   /  /::\ \:\   /  /:/  
 /__/\ \:\ \:\ /__/:/\:::::\ /__/:/\:\ \:\ /__/:/\:\_\:\ /__/:/ |:| /\ /__/:/\:\ \:\ /__/:/   
 \  \:\ \:\_\/ \__\/~|:|~~~~ \  \:\ \:\_\/ \__\/~|::\/:/ \__\/  |:|/:/ \  \:\ \:\_\/ \  \:\   
  \  \:\_\:\      |  |:|      \  \:\ \:\      |  |:|::/      |  |:/:/   \  \:\ \:\    \  \:\  
   \  \:\/:/      |  |:|       \  \:\_\/      |  |:|\/       |__|::/     \  \:\_\/     \  \:\ 
    \  \::/       |__|:|        \  \:\        |__|:|~        /__/:/       \  \:\        \  \:\
     \__\/         \__\|         \__\/         \__\|         \__\/         \__\/         \__\/
LOGO;

	public function __construct(private readonly EventDispatcherInterface $eventDispatcher)
	{
		parent::__construct('SKernel', '0.0.1');
	}

	public function getHelp(): string
	{
		return sprintf(
			"%s\n\n<fg=green;options=bold>%s</> version <fg=yellow>%s</>  %s",
			$this->logo,
			$this->getName(),
			$this->getVersion(),
			date('Y-m-d H:i:s'),
		);
	}

	/**
	 * @param InputInterface|null  $input
	 * @param OutputInterface|null $output
	 *
	 * @return int
	 * @throws Throwable
	 */
	public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
	{
		try {
			$this->eventDispatcher->dispatch(new BootApplication());
		}
		catch (Throwable $e) {
			$output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
		}

		return parent::run(...func_get_args());
	}
}