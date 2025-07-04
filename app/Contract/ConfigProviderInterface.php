<?php
declare(strict_types=1);

namespace App\Contract;

interface ConfigProviderInterface
{
	public function hasEnvFile(): bool;

	public function getRootPath(): string;

	public function pharEnable(): bool;

	public function hookFlags(): int;
}