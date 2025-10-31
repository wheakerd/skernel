<?php
declare(strict_types=1);

namespace Src\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(
	name       : 'watch',
	description: 'Watch the Phar archive.',
)]
final class WatchCommand extends Command
{
}