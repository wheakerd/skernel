<?php
declare(strict_types=1);

namespace Wheakerd\SKernel;

use Composer\Autoload\ClassLoader;
use Exception;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use PhpParser\PrettyPrinter\Standard;

/**
 * @Parser
 * @\Wheakerd\SKernel\Parser
 */
final class Parser
{
    private string $tempDirectory;

    private array $mapper = [];

    private \PhpParser\Parser $astParser;
    private Standard $printer;

    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(private ConfigProvider $configProvider)
    {
    }

    /**
     * @return void
     */
    public function parser(): void
    {
        $classMap = $this->getClassMap();

        foreach ($classMap as $classname => $file) {
            if ($this->configProvider->isFilterNameSpace($classname)) continue;

            $filepath = realpath($file);

            $stmts = $this->configProvider->astParser->parse(
                file_get_contents($filepath)
            );

            $traverser = new NodeTraverser();
            $queue = clone AstVisitorRegistry::getQueue();
            foreach ($queue as $string) {
                $visitor = new $string();
                $traverser->addVisitor($visitor);
            }

            $modifiedStmts = $traverser->traverse($stmts);

            $this->configProvider->write($filepath,
                $this->configProvider->printer->prettyPrintFile(
                    $modifiedStmts
                )
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private function getClassMap(): array
    {
        /** @var ClassLoader $classLoader */
        $classLoader = (function () {
            $classLoader = ClassLoader::getRegisteredLoaders();
            return reset($classLoader);
        })();
        return $classLoader->getClassMap();
    }
}