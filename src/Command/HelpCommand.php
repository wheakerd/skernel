<?php
declare(strict_types=1);

namespace SuperKernel\Parser\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @HelpCommand
 * @\SuperKernel\Parser\Command\HelpCommand
 */
#[AsCommand(name: 'help', description: 'Shows help')]
final class HelpCommand extends Command
{
    public function execute(InputInterface $input, OutputInterface $output): int
    {

        return Command::SUCCESS;
    }
}