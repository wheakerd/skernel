<?php
declare(strict_types=1);

namespace App\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class ClassNameResolver extends NodeVisitorAbstract
{
	private ?string $className = null;

	public function getClassName(): ?string
	{
		return $this->className;
	}

	public function enterNode(Node $node): void
	{
		if ($node instanceof Node\Stmt\Class_) {
			$this->className = $node->name->name;
		}
	}
}