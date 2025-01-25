<?php
declare(strict_types=1);

namespace Wheakerd\SKernel;

/**
 * @ProxyMetadata
 * @\Wheakerd\SKernel\ProxyMetadata
 */
final class ProxyMetadata
{
    public function __construct(
        public readonly \Closure $classname,
    )
    {

    }
}