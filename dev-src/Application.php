<?php
declare(strict_types=1);

namespace Wheakerd\SKernelDev;

use Exception;
use FilesystemIterator;
use Phar;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @Application
 * @\SuperKernel\ParserDev\Application
 */
#[AsCommand(name: 'build')]
final class Application extends Command
{
    private ?SymfonyStyle $symfonyStyle = null {
        get {
            return $this->symfonyStyle = new SymfonyStyle(new ArgvInput, new ConsoleOutput);
        }
    }
    private ?string $path = null {
        get {
            if (null === $this->path) {
                $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5(uniqid(more_entropy: true));
                is_dir($dir) || mkdir($dir, 0777, true);
                $this->path = $dir;
            }
            return $this->path;
        }
    }
    private array $mapper = [];

    public function __construct()
    {
        parent::__construct();

        if (!(file_exists(ROOT_PATH . '/composer.json') && is_file(ROOT_PATH . '/composer.json'))) {
            $this->symfonyStyle->error('Composer file does not exist');
            $this->clear();
            exit(1);
        }
        if (!is_readable(ROOT_PATH)) {
            $this->symfonyStyle->error('The current directory is not writable: ' . ROOT_PATH . ' .');
            $this->clear();
            exit(1);
        }
        if (!(is_writable(ROOT_PATH))) {
            $this->symfonyStyle->error('The current directory is not readable: ' . ROOT_PATH . ' .');
            $this->clear();
            exit(1);
        }
        $this->loadIncludes();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {

        foreach (get_declared_classes() as $class) {
            try {
                $reflection = new ReflectionClass($class);
                if ($reflection->isInternal()) continue;

                $classname = $reflection->getName();
                $filename = str_replace('\\', '_', $classname) . '.php';

                if (false === copy($reflection->getFileName(), $this->path . DIRECTORY_SEPARATOR . $filename)) {
                    $this->symfonyStyle->error('Could not copy file: ' . $filename);
                    $this->clear();
                    exit(1);
                }

                $this->mapper[$classname] = $filename;
            } catch (ReflectionException $e) {
                $this->symfonyStyle->error("'$class' reflection exception: {$e->getMessage()} .");
                $this->clear();
                exit(1);
            }
        }

        file_put_contents($this->path . DIRECTORY_SEPARATOR . 'index.php', $this->getIndex());

        $this->build();
        $this->clear();

        return Command::SUCCESS;
    }

    public function configure(): void
    {
        $this->setDescription('Parser Dev111')
            ->setHelp('This command allows ...');
    }

    private function loadIncludes(): void
    {
        $directories = array_values(
            json_decode(file_get_contents(ROOT_PATH . '/composer.json'), true) ['autoload']['psr-4'] ?? []
        );

        foreach ($directories as $directory) {
            $directory = ROOT_PATH . DIRECTORY_SEPARATOR . $directory;
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    require_once $file->getRealPath();
                }
            }
        }
    }

    private function clear(?string $path = null): void
    {
        $path = $path ?? $this->path;
        is_dir($path) || $this->symfonyStyle->warning("'$path' is not a directory");

        $files = array_diff(scandir($path), array('.', '..'));

        foreach ($files as $file) {
            $filepath = $path . DIRECTORY_SEPARATOR . $file;

            if (is_dir($filepath)) {
                $this->clear($filepath);
            } else {
                unlink($filepath);
            }
        }

        rmdir($path);
    }

    private function build(): void
    {
        $distDir = ROOT_PATH . '/dist';
        is_dir($distDir) && $this->clear($distDir);
        mkdir($distDir, 0777, true);

        try {
            $phar = new Phar($distDir . '/skernel.phar');

            $phar->setStub("<?php\r
Phar::mapPhar('skernel.phar');\r
require 'phar://skernel.phar/index.php';\r
__HALT_COMPILER(); ?>");
//            $phar->setStub($this->getStub());
            $phar->startBuffering();

            $phar->buildFromDirectory($this->path);
            $phar->stopBuffering();
            $phar->compress(Phar::GZ);

            file_put_contents($distDir . '/skernel',
                file_get_contents(ROOT_PATH . '/bin/micro.sfx')
                . file_get_contents($distDir . '/skernel.phar.gz')
            );
            chmod($distDir . '/skernel', 0755);
//            unlink($distDir . '/skernel.phar');
//            unlink($distDir . '/skernel.phar.gz');

            $this->symfonyStyle->success("'skernel' tool was built successfully");
        } catch (Exception $e) {
            $this->symfonyStyle->error("Error: " . $e->getMessage());
            $this->clear();
            exit(1);
        }
    }

    private function getIndex(): string
    {
        $mapper = '';
        foreach ($this->mapper as $key => $value) $mapper .= sprintf('\'%s\'=>\'%s\',', $key, $value);

        return sprintf("<?php\r
\$mapper=[%s];\r
spl_autoload_register(fn(\$class) => (isset(\$mapper[\$class])) && require \$mapper[\$class]);\r
new Wheakerd\SKernel\Application;", $mapper);
    }
}