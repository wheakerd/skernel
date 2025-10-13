<?php
declare(strict_types=1);

namespace Src\Generator;

use RuntimeException;
use Src\Abstract\GeneratorAbstract;
use Src\Contract\GeneratorInterface;
use Src\Enumerate\Target;
use Symfony\Component\Console\Output\OutputInterface;
use function file_get_contents;

final class BinaryGenerate extends GeneratorAbstract implements GeneratorInterface
{
	private ?string $name = null {
		get => $this->name ??= $this->composer->getPackage()->getExtra()['name'] ?? 'bin';
	}

	private mixed $resources;

	public function generate(OutputInterface $output): array
	{
		$outputFile = Target::RELEASE_DIR->path() . $this->name;

		$this->resources = fopen($outputFile, 'wb'); // 'rb' 二进制读取
		if (!$this->resources) {
			fclose($this->resources);
			throw new RuntimeException('Unable to open output file: ' . $outputFile);
		}

		$this->handleMicro();
		$this->handleConfiguration();
		$this->handlePharArchive();


		fclose($this->resources);

		$output->writeln('Please use it: <info>' . $outputFile . '</info>');

		return [];
	}

	private function handlePharArchive(): void
	{
		$filename = Target::RELEASE_DIR->path() . $this->name . '.phar';

		if (!file_exists($filename)) {
			throw new RuntimeException('The Phar archive not found.');
		}

		if (!is_readable($filename)) {
			throw new RuntimeException('The Phar archive is not readable!');
		}

		$file = fopen($filename, 'rb');

		if (!$file) {
			fclose($this->resources);
			throw new RuntimeException('Unable to open input file: ' . $filename);
		}

		// 分块读取并写入，适合大文件
		while (!feof($file)) {
			$buffer = fread($file, 8192);
			fwrite($this->resources, $buffer);
		}
	}

	private function handleConfiguration(): void
	{
		$filename = $this->sourceDirectory . '/php.ini';

		$content = '';

		if (file_exists($filename)) {
			if (!is_readable($filename)) {
				throw new RuntimeException('The php.ini file is not readable!');
			}

			$ini = file_get_contents($filename);

			$content .= "\xfd\xf6\x69\xe6";
			$content .= pack("N", strlen($ini));
			$content .= $ini;

			file_put_contents($this->sourceDirectory . '/ini.bin', $content);
		}

		fwrite($this->resources, $content);
	}

	private function handleMicro(): void
	{
		$filename = Target::BUILD_DIR->path() . 'micro.sfx';

		if (!file_exists($filename)) {
			throw new RuntimeException('The micro.sfx not found.');
		}

		if (!is_readable($filename)) {
			throw new RuntimeException('The micro.sfx is not readable!');
		}

		$file = fopen($filename, 'rb');

		if (!$file) {
			fclose($this->resources);
			throw new RuntimeException('Unable to open input file: ' . $filename);
		}

		// 分块读取并写入，适合大文件
		while (!feof($file)) {
			$buffer = fread($file, 8192);
			fwrite($this->resources, $buffer);
		}
	}
}