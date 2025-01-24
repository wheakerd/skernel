<?php
declare(strict_types=1);

namespace SuperKernel\Parser\Abstract;

/**
 * @MetadataCollector
 * @\SuperKernel\Parser\Abstract\MetadataCollector
 */
abstract class MetadataCollector
{
    static protected array $container = [];

    static protected function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, static::$container)) {
            return static::$container[$key];
        }
        return $default;
    }

    static protected function set(string $key, mixed $value): void
    {
        static::$container [$key] = $value;
    }

    static protected function has(string $key): bool
    {
        return array_key_exists($key, static::$container);
    }

    static protected function all(): array
    {
        return static::$container;
    }

    static protected function clear(): void
    {
        static::$container = [];
    }
}