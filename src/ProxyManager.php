<?php
declare(strict_types=1);

namespace Wheakerd\SKernel;

use Closure;
use SplObjectStorage;

/**
 * @ProxyManager
 * @\Wheakerd\SKernel\ProxyManager
 */
final class ProxyManager
{
    static private array $storage = [];

    static public function exist(string $classname): bool
    {
        return isset(self::$storage [$classname]);
    }

    static public function insert(string $classname, Closure $class): void
    {
        self::$storage [$classname] = new ProxyMetadata($class);
    }

    static public function remove(string $classname): void
    {
        unset(self::$storage [$classname]);
    }
}