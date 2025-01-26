<?php
declare(strict_types=1);

namespace Wheakerd\SKernel;

use Composer\Autoload\ClassLoader;
use Exception;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use PhpParser\PrettyPrinter\Standard;

/**
 * @ConfigProvider
 * @\Wheakerd\SKernel\ConfigProvider
 */
final class ConfigProvider
{
    private(set) ?\PhpParser\Parser $astParser = null {
        get {
            return clone $this->astParser ??= new ParserFactory()->createForVersion(
                PhpVersion::getNewestSupported()
            );
        }
    }
    private(set) ?Standard $printer = null {
        get {
            return clone $this->printer ??= new Standard();
        }
    }

    private ?string $workDir = null;

    public function getArguments()
    {
        return $_SERVER['argv'];
    }

    public function getWorkDir(): false|string
    {
        if (null === $this->workDir) {
            $workDir = getcwd();
            if (false === $workDir) {
                echo "The working directory is not readable: %s\n";
                exit(1);
            }
            $this->workDir = $workDir;
        }
        return getcwd();
    }
}