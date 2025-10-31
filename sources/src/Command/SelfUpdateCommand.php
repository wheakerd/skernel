<?php
declare(strict_types=1);

namespace Src\Command;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function json_decode;


//#[AsCommand(
//	name       : 'self-update',
//	description: '',
//)]
final class SelfUpdateCommand extends Command
{
	private string $releaseUri = 'https://api.github.com/repos/wheakerd/skernel/releases/latest';

	private string $destinationFile = '/usr/local/bin/skernel';

	public function execute(InputInterface $input, OutputInterface $output): int
	{
		return Command::SUCCESS;
	}

	/**
	 * @return string
	 * @throws GuzzleException
	 */
	private function getDownloadUrl(): string
	{
		$response = new Client()->get($this->releaseUri);

		$assets = json_decode($response->getBody()->getContents(), true);

		$asset = array_any($assets, fn($value,
		                               $key) => $key === 'content_type' && $value === 'application/octet-stream');
	}
}