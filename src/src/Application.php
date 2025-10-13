<?php
declare(strict_types=1);

namespace Src;

use Psr\EventDispatcher\EventDispatcherInterface;
use SuperKernel\Attribute\Contract;
use SuperKernel\Contract\ApplicationInterface;
use SuperKernel\Event\BootApplication;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[Contract(ApplicationInterface::class)]
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
		parent::__construct('SuperKernel', '1.0.0');
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

	public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
	{
		$this->eventDispatcher->dispatch(new BootApplication());

		return parent::run(...func_get_args());
	}
}