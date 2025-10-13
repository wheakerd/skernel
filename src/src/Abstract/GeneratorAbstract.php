<?php
declare(strict_types=1);

namespace Src\Abstract;

use Composer\Composer;
use Iterator;
use PhpParser\Comment\Doc;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use SplFileInfo;
use Src\CodeParser\AnnotationExtractor;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use function copy;
use function count;
use function dirname;
use function file_put_contents;
use function is_dir;
use function iterator_to_array;
use function mkdir;
use function str_replace;

abstract class GeneratorAbstract
{
	final protected Iterator $iterator;

	final protected string $sourceDirectory;

	public function __construct(protected readonly Composer $composer)
	{
		$this->sourceDirectory = dirname($this->composer->getConfig()->get('vendor-dir'));
	}

	protected function getTargetDir(): string
	{
		return $this->sourceDirectory . DIRECTORY_SEPARATOR . 'target' . DIRECTORY_SEPARATOR . 'runtime';
	}

	public function generate(OutputInterface $output): array
	{
		$parser      = new ParserFactory()->createForHostVersion();
		$printer     = new Standard();
		$annotations = [];

		$steps    = count(iterator_to_array($this->iterator));
		$progress = new ProgressBar($output, $steps);
		$progress->setFormat('[%bar%] %percent%% %elapsed:10s%');
		$progress->start();

		/* @var SplFileInfo $file */
		foreach ($this->iterator as $file) {
			$progress->advance();

			if ($file->isDir()) {
				continue;
			}

			$targetFile = $this->getTargetDir() . str_replace($this->sourceDirectory, '', $file->getRealPath());

			$targetDir = dirname($targetFile);

			// 确保目录存在
			if (!is_dir($targetDir)) {
				@mkdir($targetDir, 0777, true);
			}

			if ($file->getExtension() !== 'php') {
				copy($file->getRealPath(), $targetFile);
				continue;
			}

			// 对所有 php 文件移除注释以降低占用
			$code = file_get_contents($file->getRealPath());
			try {
				$ast = $parser->parse($code);

				$traverser = new NodeTraverser();
				// 使用自定义的 NodeVisitor 来提取注解信息
				$extractor = new AnnotationExtractor();
				$traverser->addVisitor($extractor);
				$traverser->addVisitor(new class extends NodeVisitorAbstract {
					public function enterNode(Node $node): void
					{
						// 移除普通注释
						$node->setAttribute('comments', []);

						// 移除 docblock，如果节点支持 getDocComment()
						$docComment = $node->getDocComment();
						if ($docComment instanceof Doc) {
							$node->setDocComment(new Doc('')); // 设置为空 Doc 对象
						}
					}
				});
				$ast = $traverser->traverse($ast);

				$newCode = $printer->prettyPrintFile($ast);

				file_put_contents($targetFile, $newCode);

				if ($extractor->hasAnnotation()) {
					$annotations = array_merge_recursive($annotations, $extractor->getAnnotations());
				}
			}
			catch (Error $e) {
				$output->writeln(sprintf(
					                 "<error> Parser error: %s -> %s</error>",
					                 $file->getRealPath(),
					                 $e->getMessage(),
				                 ),
				);
				continue;
			}
		}

		$progress->finish();

		$output->writeln('');

		return $annotations;
	}
}