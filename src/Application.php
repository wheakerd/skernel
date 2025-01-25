<?php
declare(strict_types=1);

namespace Wheakerd\SKernel;

use Exception;
use Symfony\Component\Console\Application as SymfonyApplication;
use Wheakerd\SKernel\Command\BuildCommand;
use Wheakerd\SKernel\Command\HelpCommand;

/**
 * @Application
 * @\Wheakerd\SKernel\Application
 */
final class Application
{
    private ?SymfonyApplication $application = null {
        get {
            return $this->application ??= new SymfonyApplication;
        }
    }

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->application->add(new HelpCommand);
        $this->application->add(new BuildCommand);

        $this->application->run();
    }
}