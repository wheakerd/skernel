<?php
declare(strict_types=1);

namespace SuperKernel\Parser\Contract;

/**
 * The annotation class must implement this interface to ensure access to the visitor behavior of the annotation class.
 * @AnnotationInterface
 * @\SuperKernel\Parser\Contract\AnnotationInterface
 */
interface AnnotationInterface
{
    public function process();
}