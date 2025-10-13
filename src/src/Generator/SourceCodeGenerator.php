<?php
declare(strict_types=1);

namespace Src\Generator;

use AppendIterator;
use CallbackFilterIterator;
use FilesystemIterator;
use IteratorIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Src\Abstract\GeneratorAbstract;
use Src\Contract\GeneratorInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class SourceCodeGenerator extends GeneratorAbstract implements GeneratorInterface
{
	public function generate(OutputInterface $output): array
	{
		$this->iterator = new AppendIterator();

		$output->writeln('<info>[INFO]</info> Scanning source code...');

		$sourcePath = dirname($this->composer->getConfig()->get('vendor-dir'));
		$psr4Dirs   = $this->composer->getPackage()->getAutoload()['psr-4'] ?? [];
		$psr4Dirs   = array_merge($psr4Dirs, $this->composer->getPackage()->getDevAutoload()['psr-4'] ?? []);
		$psr4Dirs   = array_unique(array_values($psr4Dirs));

		// 添加项目根目录下的文件（仅一层）
		$fs            = new FilesystemIterator(parent::getTargetDir(), FilesystemIterator::SKIP_DOTS);
		$rootFilesOnly = new CallbackFilterIterator($fs, fn(SplFileInfo $current): bool => $current->isFile());
		// 统一返回可迭代接口
		$this->iterator->append(new IteratorIterator($rootFilesOnly));

		foreach ($psr4Dirs as $psr4Dir) {
			$path = rtrim($sourcePath . DIRECTORY_SEPARATOR . $psr4Dir, DIRECTORY_SEPARATOR);

			if (!is_dir($path)) {
				continue;
			}

			$recursive = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
			);

			$this->iterator->append($recursive);
		}

		return parent::generate($output);
	}
}