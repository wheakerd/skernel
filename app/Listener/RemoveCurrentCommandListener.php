<?php
declare(strict_types=1);

namespace App\Listener;

use App\Command\CompileCommand;
use App\Command\PackageCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use function Hyperf\Support\env;

#[Listener]
final class RemoveCurrentCommandListener implements ListenerInterface
{
	private array $reserves = [
		CompileCommand::class,
		PackageCommand::class,
	];

	public function __construct(
		private readonly ConfigInterface $config,
	)
	{

	}

	public function listen(): array
	{
		return [
			BootApplication::class,
		];
	}

	public function process(object $event): void
	{
		//  TODO: Remove all commands provided by packages in the current project.

		if (env('APP_ENV') === 'dev') {
			return;
		}

		$commands = $this->config->get('commands', []);
		foreach ($commands as $key => $command) {
			unset($commands[$key]);
		}
		$this->config->set('commands', $commands);

		if (class_exists(AnnotationCollector::class) && class_exists(Command::class)) {
			$annotationCommands = AnnotationCollector::getClassesByAnnotation(Command::class);

			foreach ($annotationCommands as $class => $annotationCommand) {
				if (in_array($class, $this->reserves, true)) {
					continue;
				}

				AnnotationCollector::set($class . '._c.' . get_class($annotationCommand), null);
			}
		}
	}
}