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
final class Application extends SymfonyApplication
{
    private ?ConfigProvider $configProvider = null {
        get {
            return $this->configProvider ??= new ConfigProvider;
        }
    }

    /**
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct('Skernel', 'v1.0.0');
        $this->add(new BuildCommand);
        $this->add(new HelpCommand);

        $this->run();
    }
}