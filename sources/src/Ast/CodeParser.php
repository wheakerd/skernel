<?php
declare(strict_types=1);

namespace Src\Ast;

use PhpParser\Comment\Doc;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use PhpParser\PrettyPrinter\Standard;
use RuntimeException;
use function file_get_contents;

final class CodeParser
{
	private Parser   $parser;
	private Standard $printer;

	public function __construct(ParserFactory $parserFactory)
	{
		$this->printer = new Standard();
		$this->parser  = $parserFactory->createForVersion(PhpVersion::fromString('8.4'));
	}

	/**
	 * @param string $filename
	 * @param bool   $isProduction
	 *
	 * @return array{
	 *     0: string,
	 *     1: AnnotationExtractor
	 * }
	 */
	public function parse(string $filename, bool $isProduction): array
	{
		$code = file_get_contents($filename);

		try {
			$ast = $this->parser->parse($code);

			$traverser           = new NodeTraverser();
			$annotationExtractor = new AnnotationExtractor();
			$traverser->addVisitor($annotationExtractor);

			//  If it is a production environment.
			if ($isProduction) {
				$traverser->addVisitor(new class extends NodeVisitorAbstract {
					public function enterNode(Node $node): void
					{
						// Remove normal comments.
						$node->setAttribute('comments', []);

						// Remove docblock if the node supports getDocComment().
						$docComment = $node->getDocComment();
						if ($docComment instanceof Doc) {
							$node->setDocComment(new Doc('')); // Set to an empty Doc object.
						}
					}
				});
			}

			$ast = $traverser->traverse($ast);

			$newCode = $this->printer->prettyPrintFile($ast);

			return [
				$newCode,
				$annotationExtractor,
			];
		}
		catch (Error $e) {
			throw new RuntimeException(
				sprintf(
					"<error> Parser error: %s -> %s</error>",
					$filename,
					$e->getMessage(),
				),
			);
		}
	}
}