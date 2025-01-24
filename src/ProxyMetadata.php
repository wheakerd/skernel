<?php
declare(strict_types=1);

namespace SuperKernel\Parser;

/**
 * @ProxyMetadata
 * @\SuperKernel\Parser\ProxyMetadata
 */
final class ProxyMetadata
{
    public function __construct(
        public readonly \Closure $classname,
    )
    {

    }
}