<?php
declare(strict_types=1);

namespace Src\Command;

use Psr\EventDispatcher\EventDispatcherInterface;
use Src\Event\CompileEvent;
use Src\Event\PharArchiveEvent;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
	name       : 'build',
	description: 'Build binary or PHAR archive.',
	help       : 'This command allows you to build an executable binary from your project directory.',
)]
final class BuildCommand extends Command
{
	public function __construct(private readonly EventDispatcherInterface $eventDispatcher)
	{
		parent::__construct();
	}

	public function configure()
	{
		return $this
			->addOption('disable-binary', null, InputOption::VALUE_NONE, 'Disable binary build, Only build phar archive.')
			->addOption('debug', null, InputOption::VALUE_NONE, 'Enable debug mode.');
	}

	public function execute(InputInterface $input, OutputInterface $output): int
	{
		$debug = $input->getOption('debug');

		if ($debug) {
			$output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
		}

		$this->eventDispatcher->dispatch(new PharArchiveEvent($output, true));

		if (!$input->getOption('disable-binary')) {
			$this->eventDispatcher->dispatch(new CompileEvent($output));
		}

		return Command::SUCCESS;
	}
}