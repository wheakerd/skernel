<?php
declare(strict_types=1);

namespace Src\Listener;

use Composer\Composer;
use Composer\Semver\Semver;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;
use Src\Event\BeforeCompileEvent;
use Src\Provider\ConfigProvider;
use SuperKernel\Attribute\Listener;
use SuperKernel\Contract\ListenerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use function basename;
use const PHP_OS_FAMILY;

#[Listener(BeforeCompileEvent::class)]
final class BeforeCompileEventListener implements ListenerInterface
{
	private string $domain = 'https://dl.static-php.dev/';

	private string $api = 'https://dl.static-php.dev/static-php-cli/bulk/?format=json';

	public function __construct(
		private readonly Composer       $composer,
		private readonly ConfigProvider $configProvider,
		private readonly Filesystem     $filesystem,
	)
	{
	}

	/**
	 * @param object                   $event
	 *
	 * @psalm-param BeforeCompileEvent $event
	 *
	 * @return void
	 */
	public function process(object $event): void
	{
		$microSfxFile = $this->configProvider->getMicroSfx();

		if (null !== $microSfxFile) {
			$event->output->writeln('<info>[INFO]</info> The micro.sfx already exists, skipping download !');
			return;
		}

		$event->output->writeln('<info>[INFO]</info> The micro.sfx does not exist, downloading...');

		$requires = $this->composer->getPackage()->getRequires();

		if (!isset($requires['php'])) {
			throw new RuntimeException('PHP version require not configured !');
		}

		$phpConstraint = $requires['php']->getConstraint()->getPrettyString();

		$os   = match (PHP_OS_FAMILY) {
			'Darwin' => 'macos',
			'Linux'  => 'linux',
			default  => strtolower(PHP_OS_FAMILY),
		};
		$arch = php_uname('m');

		$package = $this->getDownloadLink($os, $arch, $phpConstraint);

		$extractDir = $this->configProvider->microSfxFolder . basename($package['name'], '.tar.gz');

		if (!$this->filesystem->exists($extractDir)) {
			$this->filesystem->mkdir($extractDir);
		}

		$cmd = [
			'bash',
			'-c',
			sprintf(
				'curl -L %s | tar -xz -C %s',
				escapeshellarg($this->domain . $package['full_path']),
				escapeshellarg($extractDir),
			),
		];

		$process = new Process($cmd);

		$process->run(function (string $type, string $buffer) use ($event) {
			$event->output->write($buffer);
		});

		$event->output->writeln('');

		if (!$process->isSuccessful()) {
			throw new RuntimeException($process->getIncrementalErrorOutput());
		}

		$event->output->writeln('<info>[INFO]</info> The micro.sfx download complete.');
	}

	/**
	 * @param string $os
	 * @param string $arch
	 * @param string $phpConstraint
	 *
	 * @return array{
	 *       is_dir: bool,
	 *       full_path: string,
	 *       name: string,
	 *       size: string,
	 *       last_modified: string,
	 *       download_count: int,
	 *       is_parent: bool,
	 *   }
	 */
	private function getDownloadLink(string $os, string $arch, string $phpConstraint): array
	{
		$client = new Client(['timeout' => 10]);

		try {
			$response = $client->get($this->api);
		}
		catch (GuzzleException $e) {
			throw new RuntimeException('Request failed with error: ' . $e->getMessage());
		}

		$body = $response->getBody()->getContents();

		$data = json_decode($body, true);

		$packages = array_filter(
			array   : $data,
			callback: fn($value) => str_contains($value['name'], 'micro')
			                        && str_contains($value['name'], $os)
			                        && str_contains($value['name'], $arch),
			mode    : ARRAY_FILTER_USE_BOTH,
		);

		/**
		 * @var array{
		 *      is_dir: bool,
		 *      full_path: string,
		 *      name: string,
		 *      size: string,
		 *      last_modified: string,
		 *      download_count: int,
		 *      is_parent: bool,
		 *  } $package
		 */
		while ($package = array_pop($packages)) {
			$version = null;

			if (preg_match('/php-(\d+\.\d+\.\d+)-micro-[\w-]+\.tar\.gz$/', $package['name'], $matches)) {
				$version = $matches[1];
			}

			if (null === $version) {
				continue;
			}

			if (Semver::satisfies($version, $phpConstraint)) {
				return $package;
			}
		}

		throw new RuntimeException(
			sprintf(
				format: 'The configuration item of require.php is %s, and there is no matching micro.sfx file !',
				values: $phpConstraint,
			),
		);
	}
}