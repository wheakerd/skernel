<?php
declare(strict_types=1);

namespace Src\Utils;

use Composer\Composer;
use Composer\IO\NullIO;
use Composer\Json\JsonValidationException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use function getcwd;

final readonly class Factory
{
	public function __construct(private LoggerInterface $logger)
	{
	}

	public function getComposer(): Composer
	{
		$currentDirectory = getcwd();
		$io               = new NullIO();

		try {
			return (new \Composer\Factory)->createComposer($io, $currentDirectory . '/composer.json');
		}
		catch (JsonValidationException $e) {
			$this->logger->error($e->getMessage());

			throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
		}
	}
}