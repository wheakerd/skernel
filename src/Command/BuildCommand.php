<?php
declare(strict_types=1);

namespace Wheakerd\SKernel\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @HelpCommand
 * @\Wheakerd\SKernel\Command\HelpCommand
 */
#[AsCommand(name: 'build', description: 'Shows help')]
final class BuildCommand extends Command
{
    public function execute(InputInterface $input, OutputInterface $output): int
    {

        return Command::SUCCESS;
    }
}