<?php
declare(strict_types=1);

namespace Src\Cache;

use Src\Abstract\CacheAbstract;
use function filemtime;

final class ClassnameCache extends CacheAbstract
{
	/**
	 * @var array<string, int> $files
	 */
	private(set) array $files = [];

	/**
	 * @var array<string, array> $attributes
	 */
	private(set) array $attributes = [];

	public function addAttribute(string $filename, array $attributes): void
	{
		$cacheFilename = $this->getRelativePath($filename);

		$this->attributes[$cacheFilename] = $attributes;
	}

	public function setUpdateFile(string $filename, int $updateTime): void
	{
		$this->files[$filename] = $updateTime;
	}

	public function isShouldUpdate(string $filename): bool
	{
		$cacheFilename = $this->getRelativePath($filename);

		if ($updateTime = $this->cache?->files[$cacheFilename] ?? false) {
			$isShould = filemtime($filename) > $updateTime;

			if ($isShould) {
				return true;
			}

			$this->attributes[$cacheFilename] = $this->cache?->attributes[$cacheFilename] ?? [];
			$this->files[$cacheFilename]      = $updateTime;
		}

		return true;
	}

	private function getRelativePath(string $filename): string
	{
		return str_replace($this->configProvider->homeFolder, '', $filename);
	}

	public function reuse(string $name): void
	{
		$this->attributes[$name] = $this->cache?->attributes ?? [];
	}

	protected function getCacheName(): string
	{
		return 'attribute';
	}

	public function getAttributes(): array
	{
		$attributes = [];

		foreach ($this->attributes as $attribute) {
			$attributes = array_merge_recursive($attributes, $attribute);
		}

		return $attributes;
	}

	public function __sleep(): array
	{
		return [
			'files',
			'attributes',
		];
	}
}