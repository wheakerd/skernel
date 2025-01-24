<?php
declare(strict_types=1);

namespace SuperKernel\Parser\Visitor;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitorAbstract;

/**
 * @ClassHandlerVisitor
 * @\SuperKernel\Di\Parser\ClassHandlerVisitor
 */
final class ClassHandlerVisitor extends NodeVisitorAbstract
{
    private string $classname;

    private array $properties = [];

    private array $methods = [];

    private ?Name $extends = null;


    public function __construct()
    {
    }

    public function enterNode(Node $node): void
    {
        if ($node instanceof Class_) {
            $this->classname = $node->name->name;
            var_dump($node);
            $this->extends = $node->extends;
        }
        if ($node instanceof ClassMethod) {
            $classMethod = clone $node;
            $classMethod->attrGroups = [];
            $this->methods [$node->name->name] = $classMethod;

        }
    }

    public function afterTraverse(array $nodes): ?array
    {
        $nodes [] = new Expression(
            new StaticCall(new Name('\SuperKernel\Parser\ProxyManager'), 'insert', [
                new ClassConstFetch(new Name($this->classname), 'class'),
                new Arg(new Closure([
                    'stmts' => [
                        new Return_(
                            new New_(
                                new Class_(null, [
                                    'stmts' => $this->methods,
                                    'extends' => $this->extends,
                                ]),
                            )
                        )
                    ],
                ])),
            ])
        );

        return $nodes;
    }
}