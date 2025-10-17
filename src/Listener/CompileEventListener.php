<?php
declare(strict_types=1);

namespace Src\Listener;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Src\Event\BeforeCompileEvent;
use Src\Event\CompileEvent;
use Src\Provider\ConfigProvider;
use SuperKernel\Attribute\Listener;
use SuperKernel\Contract\ListenerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;
use function fclose;
use function feof;
use function fopen;
use function fread;
use function fwrite;

#[Listener(CompileEvent::class)]
final class CompileEventListener implements ListenerInterface
{
	private ?EventDispatcherInterface $eventDispatcher = null {
		get => $this->eventDispatcher ??= $this->container->get(EventDispatcherInterface::class);
	}

	public function __construct(
		private readonly ContainerInterface $container,
		private readonly Filesystem         $filesystem,
		private readonly ConfigProvider     $configProvider,
	)
	{
	}

	/**
	 * @param object             $event
	 *
	 * @psalm-param CompileEvent $event
	 *
	 * @return void
	 */
	public function process(object $event): void
	{
		$this->eventDispatcher->dispatch(new BeforeCompileEvent($event->output));


		$binaryFilename = $this->configProvider->binaryFilename;

		if ($this->filesystem->exists($binaryFilename)) {
			$this->filesystem->remove($binaryFilename);
		}

		$resources = fopen($binaryFilename, 'wb');

		if (!$resources) {
			fclose($resources);
			throw new RuntimeException('Error during compilation: ' . $binaryFilename);
		}

		try {
			$microSfxName = $this->configProvider->getMicroSfx() . DIRECTORY_SEPARATOR . 'micro.sfx';

			// Handle micro.sfx
			$this->processFile($microSfxName, $resources, 'micro.sfx');
			// Handle php.ini
			$this->processIni($resources, $event->output);
			// Handle phar
			$this->processFile($this->configProvider->pharFilename, $resources, 'phar');
		}
		catch (Throwable $throwable) {
			fclose($resources);
			throw new RuntimeException($throwable->getMessage());
		}

		fclose($resources);
		$event->output->writeln('<info>[INFO] </info>Please use it: ' . $binaryFilename);
	}

	private function processFile(?string $filename, $resources, string $type): void
	{
		if (null === $filename || !$this->filesystem->exists($filename)) {
			throw new RuntimeException("The $filename file does not exist. Please submit an issue to skernel!");
		}

		$file = $this->openFile($filename);

		while (!feof($file)) {
			$buffer = fread($file, 8192);

			if ($buffer === false) {
				throw new RuntimeException("Error reading $type file.");
			}

			fwrite($resources, $buffer);
		}
		fclose($file);
	}

	private function processIni($resources, OutputInterface $output): void
	{
		$iniFilename = $this->configProvider->homeFolder . 'php.ini';

		if (!$this->filesystem->exists($iniFilename)) {
			$output->writeln('<info>[INFO] </info> The ' . $iniFilename . ' file does not exist, skip Mount.');
			return;
		}

		if (!is_readable($iniFilename)) {
			throw new RuntimeException('The php.ini file is not readable!');
		}

		$iniContent = $this->filesystem->readFile($iniFilename);
		$iniContent = "\xfd\xf6\x69\xe6" . pack("N", strlen($iniContent)) . $iniContent;

		fwrite($resources, $iniContent);
	}

	private function openFile(string $filename)
	{
		$file = fopen($filename, 'rb');
		if (!$file) {
			throw new RuntimeException("Unable to open file: $filename");
		}
		return $file;
	}
}