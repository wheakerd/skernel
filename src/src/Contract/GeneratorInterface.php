<?php
declare(strict_types=1);

namespace Src\Contract;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface GeneratorInterface
{
	public function generate(OutputInterface $output): array;
}