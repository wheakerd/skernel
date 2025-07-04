<?php
declare(strict_types=1);

namespace App\Command;

use App\Support\Version;
use Hyperf\Command\Annotation\Command;
use Hyperf\Phar\BuildCommand;
use InvalidArgumentException;
use Phar;
use Throwable;
use UnexpectedValueException;
use function Hyperf\Support\env;

#[Command(name: 'run:build')]
final class PharBuildCommand extends BuildCommand
{
	private string $bin             = 'bin/hyperf.php';
	private string $pharVersionPath = BASE_PATH . '/.phar.version';
	private string $version         = '0.0.0';

	/**
	 * @return void
	 */
	public function handle(): void
	{
		$this->input->setOption('name', $this->getPackageName());
		$this->input->setOption('bin', $this->getMain());
		$this->input->setOption('path', BASE_PATH);
		$this->input->setOption('phar-version', $this->getPharVersion());
		$this->input->setOption('mount', [
			'.env.local',
			'assets',
			'bin/spc',
			'bin/micro.sfx',
			'bin/stub.php',
			'skernel',
			'skernel.phar',
		]);

		$pharFile = BASE_PATH . DIRECTORY_SEPARATOR . $this->getPackageName();

		if (file_exists($pharFile) && is_file($pharFile)) {
			unlink($pharFile);
		}

		parent::handle();

		$this->line('PHAR package created successfully.', 'info');
		$this->line('Compiling phar archive into binary executable file.', 'info');

		$this->compile();

		$this->line('Compilation successful.', 'info');
	}

	public function getMain(): string
	{
		$bin = BASE_PATH . DIRECTORY_SEPARATOR . $this->bin;

		if (file_exists($bin) && is_file($bin)) {
			return $this->bin;
		}

		throw new InvalidArgumentException('No execution entry file exists.');
	}

	private function getPharVersion(): string
	{
		if (!file_exists($this->pharVersionPath)) {
			file_put_contents($this->pharVersionPath, '0.0.0');
		} else {
			//  Read the current version number
			$this->version = trim(file_get_contents($this->pharVersionPath));
		}

		//  Check if the version number format is valid
		if (preg_match('/^\d+\.\d+\.\d+$/', $this->version) !== 1) {
			throw new InvalidArgumentException('Invalid version format in .phar.version.');
		}

		$newVersion = Version::semVer($this->version);

		try {
			file_put_contents($this->pharVersionPath, $newVersion);
			$this->line("Version updated successfully to $newVersion.", 'info');
		}
		catch (Throwable $e) {
			$this->line('Failed to update version: ' . $e->getMessage(), 'info');
		}

		return $newVersion;
	}

	public function getPackageName(): string
	{
		$name = env('APP_NAME');

		if (is_null($name)) {
			throw new UnexpectedValueException(
				'APP_NAME does not exist in your .env.local file, please update your .env.local',
			);
		}

		return "$name.phar";
	}

	private function compile(): void
	{
		$pharName = $this->getPackageName();
		$phar     = new Phar(BASE_PATH . DIRECTORY_SEPARATOR . $pharName, 0, $pharName);

		$phar->setStub(<<<EOF
		<?php
		require __DIR__ . '/bin/hyperf.php';
		__HALT_COMPILER(); ?>
		EOF,
		);

		$name = env('APP_NAME');

		shell_exec("./bin/spc micro:combine ./$pharName --with-micro=./bin/micro.sfx --with-ini-file=./php.ini --output=$name");
	}
}