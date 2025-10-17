<?php
declare(strict_types=1);

namespace Src\Event;

use Symfony\Component\Console\Output\OutputInterface;

final class PharArchiveEvent
{
	public function __construct(public OutputInterface $output, public bool $requireDev)
	{
	}
}