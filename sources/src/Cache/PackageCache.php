<?php
declare(strict_types=1);

namespace Src\Cache;

use Src\Abstract\CacheAbstract;

final class PackageCache extends CacheAbstract
{

	private(set) array $packages = [];

	public function addAttribute(string $packageName, array|false|string $relativePath): void
	{
		$this->packages[$packageName][] = $relativePath;
	}

	protected function getCacheName(): string
	{
		return 'package';
	}

	public function reuse(string $name): void
	{
		$package = $this->cache?->packages[$name] ?? [];

		$this->packages[$name] = $package;
	}

	public function getAttributes(): array
	{
		$packages = [];

		foreach ($this->packages as $package) {
			$packages = array_merge_recursive($packages, ...$package);
		}

		return $packages;
	}

	public function __sleep(): array
	{
		return ['packages'];
	}
}