<?php
declare(strict_types=1);

namespace Src\Command;

use Psr\Container\ContainerInterface;
use Src\Generator\BinaryGenerate;
use Src\Generator\PackageGenerator;
use Src\Generator\PharArchiveGenerator;
use Src\Generator\SourceCodeGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'build',
    description: 'Build binary or PHAR archive.',
    help: 'This command allows you to build an executable binary from your project directory.',
)]
final class BuildCommand extends Command
{
    private ?PackageGenerator $packageGenerator = null {
        get => $this->packageGenerator ??= $this->container->get(PackageGenerator::class);
    }

    private ?SourceCodeGenerator $sourceCodeGenerator = null {
        get => $this->sourceCodeGenerator ??= $this->container->get(SourceCodeGenerator::class);
    }

    private ?PharArchiveGenerator $pharArchiveGenerator = null {
        get => $this->pharArchiveGenerator ??= $this->container->get(PharArchiveGenerator::class);
    }

    private ?BinaryGenerate $binaryGenerate = null {
        get => $this->binaryGenerate ??= $this->container->get(BinaryGenerate::class);
    }

    public function __construct(private readonly ContainerInterface $container)
    {
        parent::__construct();
    }

    public function configure()
    {
        return $this
            ->addOption('disable-binary', null, InputOption::VALUE_NONE, 'Disable binary build, Only build phar archive.')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Enable debug mode.');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $debug = $input->getOption('debug');

        if ($debug) {
            $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        }

        $annotations = [];

        $annotations = array_merge_recursive($annotations, $this->packageGenerator->generate($output));
        $annotations = array_merge_recursive($annotations, $this->sourceCodeGenerator->generate($output));

        $output->writeln('<info>Building Phar archive...</info>');

        $this->pharArchiveGenerator->generate($annotations);

        $disableBinary = $input->getOption('disable-binary');

        if ($disableBinary) {
            $output->writeln('<info>PHAR archive created!</info>');

            return Command::SUCCESS;
        }

        try {
            $this->binaryGenerate->generate($output);
        } catch (Throwable $throwable) {

            $output->writeln(sprintf('<error>%s</error>', $throwable->getMessage()));

            return Command::FAILURE;
        }

        $output->writeln('<info>Binary file created!</info>');

        return Command::SUCCESS;
    }
}