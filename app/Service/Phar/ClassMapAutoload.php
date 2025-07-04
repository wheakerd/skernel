<?php
declare(strict_types=1);

namespace App\Service\Phar;

use Composer\Autoload\AutoloadGenerator;
use Composer\ClassMapGenerator\ClassMapGenerator;
use Composer\Composer;
use Composer\Config;
use Composer\Console\Application;
use Composer\Installer\InstallationManager;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Json\JsonValidationException;
use Composer\Package\RootPackageInterface;
use Composer\Pcre\Preg;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\Filesystem;
use Composer\Util\Platform;
use Seld\JsonLint\ParsingException;
use Symfony\Component\Console\Formatter\OutputFormatter;
use function count;

final class ClassMapAutoload
{
	private Composer $composer;

	private Config $config;

	private IOInterface $io;

	private RootPackageInterface $package;

	private InstalledRepositoryInterface $localRepo;

	private InstallationManager $installationManager;

	private AutoloadGenerator $autoloadGenerator;

	/**
	 * @throws JsonValidationException
	 */
	public function __construct(private bool $devMode = false)
	{
		$application = new Application('Skernel');

		$this->composer            = $application->getComposer(true, true, true);
		$this->io                  = $application->getIO();
		$this->package             = $this->composer->getPackage();
		$this->config              = $this->composer->getConfig();
		$this->localRepo           = $this->composer->getRepositoryManager()->getLocalRepository();
		$this->installationManager = $this->composer->getInstallationManager();
		$this->autoloadGenerator   = $this->composer->getAutoloadGenerator();
	}

	public function setDevMode(bool $devMode = true): void
	{
		$this->devMode = $devMode;
	}

	/**
	 * @return string
	 * @throws ParsingException
	 */
	public function getStub(): string
	{
		foreach ($this->localRepo->getCanonicalPackages() as $localPkg) {
			$installPath = $this->installationManager->getInstallPath($localPkg);
			if ($installPath !== null && file_exists($installPath) === false) {
				$this->io->write('<warning>Not all dependencies are installed. Make sure to run a "composer install" to install missing dependencies</warning>');

				break;
			}
		}

		$this->autoloadGenerator->setDevMode($this->devMode);

//		$classMap = $this->autoloadGenerator->dump(
//			$this->config,
//			$this->localRepo,
//			$this->package,
//			$this->installationManager,
//			'composer',
//			true,
//			null,
//			$this->locker,
//		);

		return sprintf($this->stub, $this->getClassMap());
	}

	/**
	 * @return string
	 * @throws ParsingException
	 */
	private function getClassMap(): string
	{
		// auto-set devMode based on whether dev dependencies are installed or not
		if (false === $this->devMode) {
			$installedJson = new JsonFile($this->config->get('vendor-dir') . '/composer/installed.json');
			if ($installedJson->exists()) {
				$installedJson = $installedJson->read();
				if (isset($installedJson['dev'])) {
					$this->devMode = $installedJson['dev'];
				}
			}
		}

		$classMapGenerator = new ClassMapGenerator(
			[
				'php',
				'inc',
				'hh',
			],
		);
		$classMapGenerator->avoidDuplicateScans();

		$filesystem = new Filesystem();
		$filesystem->ensureDirectoryExists($this->config->get('vendor-dir'));

		$basePath   = $filesystem->normalizePath(realpath(realpath(Platform::getCwd())));
		$vendorPath = $filesystem->normalizePath(realpath(realpath($this->config->get('vendor-dir'))));
		$targetDir  = $vendorPath . DIRECTORY_SEPARATOR . 'composer';
		$filesystem->ensureDirectoryExists($targetDir);

		// Collect information from all packages.
		$devPackageNames = $this->localRepo->getDevPackageNames();
		$packageMap      = $this->autoloadGenerator->buildPackageMap($this->installationManager, $this->package, $this->localRepo->getCanonicalPackages());

		if ($this->devMode) {
			// if dev mode is enabled, then we do not filter any dev packages out so disable this entirely
			$filteredDevPackages = false;
		} else {
			// if the list of dev package names is available we use that straight, otherwise pass true which means use legacy algo to figure them out
			$filteredDevPackages = $devPackageNames ?: true;
		}
		$autoloads = $this->autoloadGenerator->parseAutoloads($packageMap, $this->package, $filteredDevPackages);

		$excluded = [];
		if (!empty($autoloads['exclude-from-classmap'])) {
			$excluded = $autoloads['exclude-from-classmap'];
		}

		foreach ($autoloads['classmap'] as $dir) {
			$classMapGenerator->scanPaths($dir, $this->buildExclusionRegex($dir, $excluded));
		}

		$namespacesToScan = [];

		// Scan the PSR-0/4 directories for class files, and add them to the class map
		foreach ([
			         'psr-4',
			         'psr-0',
		         ] as $psrType) {
			foreach ($autoloads[$psrType] as $namespace => $paths) {
				$namespacesToScan[$namespace][] = [
					'paths' => $paths,
					'type'  => $psrType,
				];
			}
		}

		krsort($namespacesToScan);

		foreach ($namespacesToScan as $namespace => $groups) {
			foreach ($groups as $group) {
				foreach ($group['paths'] as $dir) {
					$dir = $filesystem->normalizePath($filesystem->isAbsolutePath($dir) ? $dir : $basePath . '/' . $dir);
					if (!is_dir($dir)) {
						continue;
					}

					// if the vendor dir is contained within a psr-0/psr-4 dir being scanned we exclude it
					if (str_contains($vendorPath, $dir . '/')) {
						$exclusionRegex = $this->buildExclusionRegex($dir, array_merge($excluded, [$vendorPath . '/']));
					} else {
						$exclusionRegex = $this->buildExclusionRegex($dir, $excluded);
					}

					$classMapGenerator->scanPaths($dir, $exclusionRegex, $group['type'], $namespace);
				}
			}
		}

		$classMap         = $classMapGenerator->getClassMap();
		$ambiguousClasses = $classMap->getAmbiguousClasses();

		foreach ($ambiguousClasses as $className => $ambiguousPaths) {
			if (count($ambiguousPaths) > 1) {
				$this->io->writeError(
					'<warning>Warning: Ambiguous class resolution, "' . $className . '"' .
					' was found ' . (count($ambiguousPaths) + 1) . 'x: in "' . $classMap->getClassPath($className) . '" and "' . implode('", "', $ambiguousPaths) . '", the first will be used.</warning>',
				);
			} else {
				$this->io->writeError(
					'<warning>Warning: Ambiguous class resolution, "' . $className . '"' .
					' was found in both "' . $classMap->getClassPath($className) . '" and "' . implode('", "', $ambiguousPaths) . '", the first will be used.</warning>',
				);
			}
		}
		if (count($ambiguousClasses) > 0) {
			$this->io->writeError('<info>To resolve ambiguity in classes not under your control you can ignore them by path using <href=' . OutputFormatter::escape('https://getcomposer.org/doc/04-schema.md#exclude-files-from-classmaps') . '>exclude-files-from-classmap</>');
		}

		// output PSR violations which are not coming from the vendor dir
		$classMap->clearPsrViolationsByPath($vendorPath);
		foreach ($classMap->getPsrViolations() as $msg) {
			$this->io->writeError("<warning>$msg</warning>");
		}

		$classMap->addClass('Composer\InstalledVersions', $vendorPath . '/composer/InstalledVersions.php');
		$classMap->sort();

		$classmapFile = '';
		foreach ($classMap->getMap() as $className => $path) {
			$pathCode     = $this->getPathCode($filesystem, $basePath, $vendorPath, $path) . ",\n";
			$classmapFile .= '    ' . var_export($className, true) . ' => ' . $pathCode;
		}

		return $classmapFile;
	}


	/**
	 * @param Filesystem $filesystem
	 * @param string     $basePath
	 * @param string     $vendorPath
	 * @param string     $path
	 *
	 * @return string
	 */
	private function getPathCode(Filesystem $filesystem, string $basePath, string $vendorPath, string $path): string
	{
		if (!$filesystem->isAbsolutePath($path)) {
			$path = $basePath . '/' . $path;
		}
		$path = $filesystem->normalizePath($path);

		$baseDir = '';
		if (str_starts_with($path . '/', $vendorPath . '/')) {
			$path    = '/../vendor' . substr($path, strlen($vendorPath));
			$baseDir = '__DIR__ . ';
		} else {
			$path = $filesystem->normalizePath($filesystem->findShortestPath($basePath, $path, true));
			if (!$filesystem->isAbsolutePath($path)) {
				$baseDir = '__DIR__ . ';
				$path    = '/../' . $path;
			}
		}

		if (Preg::isMatch('{\.phar([\\\\/]|$)}', $path)) {
			$baseDir = "'phar://' . " . $baseDir;
		}

		return $baseDir . var_export($path, true);
	}

	private string $stub = <<<'EOF'
	<?php
	/**
	 * This Phar stub header was automatically generated by the Skernel tool.
	 *
	 * It provides the bootstrap logic for loading the application's class map
	 * and initializing the autoloader within the Phar archive.
	 *
	 * Do not modify this file manually unless you know what you're doing.
	 */
	ini_set('display_errors', 'on');
	ini_set('display_startup_errors', 'on');
	ini_set('memory_limit', '1G');
	
	error_reporting(E_ALL);
	
	$classMap = [
	%s];
	
	$autoloader = new readonly class ($classMap) {
		public function __construct(private array $classMap)
		{
		}
	
		public function getClassMap(): array
		{
			return $this->classMap;
		}
	
		public function autoload(string $class): void
		{
			if (isset($this->classMap[$class])) {
				require $this->classMap[$class];
			}
		}
	};
	
	spl_autoload_register([$autoloader,'autoload'], true, true);
	__HALT_COMPILER(); ?>
	EOF;


	/**
	 * @param array<string> $excluded
	 *
	 * @return non-empty-string|null
	 */
	private function buildExclusionRegex(string $dir, array $excluded): ?string
	{
		if ([] === $excluded) {
			return null;
		}

		// filter excluded patterns here to only use those matching $dir
		// exclude-from-classmap patterns are all realpath so we can only filter them if $dir exists so that realpath($dir) will work
		// if $dir does not exist, it should anyway not find anything there so no trouble
		if (file_exists($dir)) {
			// transform $dir in the same way that exclude-from-classmap patterns are transformed so we can match them against each other
			$dirMatch = preg_quote(strtr(realpath($dir), '\\', '/'));
			foreach ($excluded as $index => $pattern) {
				// extract the constant string prefix of the pattern here, until we reach a non-escaped regex special character
				$pattern = Preg::replace('{^(([^.+*?\[^\]$(){}=!<>|:\\\\#-]+|\\\\[.+*?\[^\]$(){}=!<>|:#-])*).*}', '$1', $pattern);
				// if the pattern is not a subset or superset of $dir, it is unrelated and we skip it
				if (!str_starts_with($pattern, $dirMatch) && !str_starts_with($dirMatch, $pattern)) {
					unset($excluded[$index]);
				}
			}
		}

		return count($excluded) > 0 ? '{(' . implode('|', $excluded) . ')}' : null;
	}
}