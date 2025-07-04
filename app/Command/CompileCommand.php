<?php

namespace App\Command;

use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;

#[Command(name: 'compile')]
final class CompileCommand extends HyperfCommand
{
}