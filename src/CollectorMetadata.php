<?php
declare(strict_types=1);

namespace SuperKernel\Parser;

use PhpParser\Node\AttributeGroup;

/**
 * @CollectorMetadata
 * @\SuperKernel\Parser\CollectorMetadata
 */
final class CollectorMetadata
{
    public array $class = [];
    public array $methods = [];
    public array $properties = [];

    public function setClassname(string $name): void
    {
        $this->class += compact('name');
    }

    public function setClassAttributeGroup(AttributeGroup $attributeGroup): void
    {
        $this->class += compact('attributeGroup');
    }

    public function setMethodName(string $name): void
    {
        $this->methods += compact('name');
    }

    public function setMethodAttributeGroup(AttributeGroup $attributeGroup): void
    {
        $this->methods += compact('attributeGroup');
    }
}