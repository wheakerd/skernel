<?php
declare(strict_types=1);

namespace SuperKernel\Parser;

use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use PhpParser\PrettyPrinter\Standard;

//use SuperKernel\Di\Parser\VisitorMetadata;

/**
 * @Ast
 * @\SuperKernel\Parser\Ast
 * @method static proxy(string $code)
 */
final class Ast
{
    static protected self|null $parser = null;

    protected Parser $astParser;

    protected Standard $printer;

    public function __construct()
    {
        $this->astParser = new ParserFactory()->createForVersion(
            PhpVersion::getNewestSupported()
        );
        $this->printer = new Standard();
    }

    public function proxy(string $code): string
    {
        $stmts = $this->astParser->parse($code);
        $traverser = new NodeTraverser();
        $queue = clone AstVisitorRegistry::getQueue();
        foreach ($queue as $string) {
            $visitor = new $string();
            $traverser->addVisitor($visitor);
        }
        $modifiedStmts = $traverser->traverse($stmts);

        return $this->printer->prettyPrintFile(
            $modifiedStmts
        );
    }
}