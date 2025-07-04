<?php
ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');
ini_set('memory_limit', '1G');

error_reporting(E_ALL);

$classMap = [];

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

spl_autoload_register([$autoloader, 'autoload'], true, true);
__HALT_COMPILER(); ?>