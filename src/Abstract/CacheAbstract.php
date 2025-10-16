<?php
declare(strict_types=1);

namespace Src\Abstract;

use Src\Provider\ConfigProvider;
use Symfony\Component\Filesystem\Filesystem;
use function dirname;

abstract class CacheAbstract
{
	final protected ?CacheAbstract $cache = null;

	private string $filename;

	public function __construct(
		protected readonly ConfigProvider $configProvider,
		private readonly Filesystem       $filesystem,
	)
	{
		$this->filename = $this->configProvider->cacheFolder . $this->getCacheName() . '.cache';

		if (file_exists($this->filename)) {
			$this->cache = unserialize(file_get_contents($this->filename));
		}
	}

	abstract protected function getCacheName(): string;

	abstract public function reuse(string $name): void;

	abstract public function __sleep(): array;

	final public function writeCache(): void
	{
		$dir = dirname($this->filename);

		if (!$this->filesystem->exists($dir)) {
			$this->filesystem->mkdir($dir);
		}

		file_put_contents($this->filename, serialize($this));
	}
}