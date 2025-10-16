<?php
declare(strict_types=1);

namespace Src\Ast;

use SplFileInfo;
use Src\Cache\ClassnameCache;
use Src\Cache\PackageCache;
use Src\Provider\ConfigProvider;
use Symfony\Component\Filesystem\Filesystem;

final readonly class Scanner
{
	public function __construct(
		private CodeParser     $codeParser,
		private ClassnameCache $classnameCache,
		private PackageCache   $packageCache,
		private Filesystem     $filesystem,
		private ConfigProvider $configProvider,
	)
	{
	}

	public function scan(SplFileInfo $file, bool $isProduction, ?string $packageName = null): void
	{
		$realPath     = $file->getRealPath();
		$relativePath = str_replace($this->configProvider->homeFolder, '', $realPath);
		$targetFile   = $this->configProvider->runtimeFolder . str_replace($this->configProvider->homeFolder, '', $realPath);
		$targetDir    = dirname($targetFile);

		if (!is_dir($targetDir)) {
			$this->filesystem->mkdir($targetDir);
		}

		if ($file->getExtension() !== 'php') {
			$this->filesystem->copy($realPath, $targetFile);
			return;
		}

		if (!$this->classnameCache->isShouldUpdate($realPath)) {
			$this->classnameCache->reuse($relativePath);

			return;
		}

		[
			$code,
			$annotationExtractor,
		] = $this->codeParser->parse($realPath, $isProduction);

		if ($annotationExtractor->hasAnnotation()) {
			null === $packageName
				? $this->classnameCache->addAttribute($relativePath, $annotationExtractor->getAnnotations())
				: $this->packageCache->addAttribute($packageName, $annotationExtractor->getAnnotations());

		}

		$this->classnameCache->setUpdateFile($relativePath, fileatime($realPath));

		$this->filesystem->dumpFile($targetFile, $code);
	}
}