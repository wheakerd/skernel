<?php
declare(strict_types=1);

namespace SuperKernel\Parser;

use Closure;
use ReflectionClass;

/**
 * @MetadataCollector
 * @\SuperKernel\Parser\MetadataCollector
 */
final class MetadataCollector
{
    static private array $container = [];

    static public function collectSelf(string $class, Closure $callback): void
    {
        self::$container[$class]['_f'] = $callback;
    }

    static public function collectClass(string $class, string $annotation, mixed $value): void
    {
        self::$container[$class]['_c'][$annotation] = $value;
    }

    static public function collectProperty(string $class, string $property, string $annotation, mixed $value): void
    {
        self::$container[$class]['_p'][$property][$annotation] = $value;
    }

    static public function collectMethod(string $class, string $method, string $annotation, mixed $value): void
    {
        self::$container[$class]['_m'][$annotation] = $value;
    }

//    static  public function ()
//    {
//
//    }
}