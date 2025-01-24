<?php
declare(strict_types=1);

namespace SuperKernel\Parser;

use Composer\Autoload\ClassLoader;
use Exception;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use PhpParser\PrettyPrinter\Standard;

/**
 * @ConfigProvider
 * @\SuperKernel\Parser\ConfigProvider
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

    private string $tempDirectory;

    /**
     * @param string $runtimePath
     * @param array $filterNameSpace
     * @throws Exception
     */
    public function __construct()
    {
        var_dump($_SERVER['argv']);die;
//        if (!is_writable($runtimePath)) {
//            throw new Exception('Runtime path "' . $runtimePath . '" is not writable.');
//        }
//        $this->runtimePath = realpath($runtimePath);
//
//        $this->tempDirectory = $this->runtimePath . DIRECTORY_SEPARATOR . md5(uniqid(more_entropy: true));
//
//        if (false === mkdir($this->tempDirectory, 0777, true)) {
//            throw new Exception(
//                sprintf(
//                    'Failed to create directory "%s"', $this->tempDirectory
//                )
//            );
//        }
    }

    /**
     * @return array<string, string>
     */
    public function getCLassMap(): array
    {
        /** @var ClassLoader $classLoader */
        $classLoader = (function () {
            $classLoader = ClassLoader::getRegisteredLoaders();
            return reset($classLoader);
        })();
        return $classLoader->getClassMap();
    }

    public function isFilterNameSpace(string $namespace): bool
    {
        return array_any($this->filterNameSpace,
            fn($name) => str_starts_with($name, $namespace)
        );
    }

    public function write(string $path, string $content): void
    {
        $path = substr($this->runtimePath, strlen($path) + 1);

//        var_dump($this->runtimePath, $path);
    }

    public function getMapper(): array
    {
        return [];
    }
}