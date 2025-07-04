<?php
declare(strict_types=1);

namespace App\Support;

use App\Contract\ConfigProviderInterface;
use Hyperf\Engine\DefaultOption;
use Phar;

final class ConfigProvider implements ConfigProviderInterface
{
	private string $rootPath;
	private bool   $pharEnable;
	private int    $hookFlags;

	public function hasEnvFile(): bool
	{
		return file_exists($this->getRootPath() . '/.env');
	}

	public function getRootPath(): string
	{
		if (!isset($this->rootPath)) {
			$phar_enable    = Phar::running(false);
			$this->rootPath = strlen($phar_enable) ? dirname($phar_enable) : BASE_PATH;
		}

		return $this->rootPath;
	}

	public function pharEnable(): bool
	{
		if (!isset($this->pharEnable)) {
			$this->pharEnable = !!strlen(Phar::running(false));
		}

		return $this->pharEnable;
	}

	/**
	 * @return int Return the hook_ flags for coroutine configuration.
	 */
	public function hookFlags(): int
	{
		if (!isset($this->hookFlags)) {
			$this->hookFlags = $this->pharEnable() ?
				SWOOLE_HOOK_TCP
				| SWOOLE_HOOK_UNIX
				| SWOOLE_HOOK_UDP
				| SWOOLE_HOOK_UDG
				| SWOOLE_HOOK_SSL
				| SWOOLE_HOOK_TLS
				| SWOOLE_HOOK_SLEEP
				| SWOOLE_HOOK_STREAM_FUNCTION
				| SWOOLE_HOOK_BLOCKING_FUNCTION
				| SWOOLE_HOOK_PROC
				| SWOOLE_HOOK_NATIVE_CURL
				| SWOOLE_HOOK_SOCKETS
				| SWOOLE_HOOK_STDIO
				: DefaultOption::hookFlags();
		}

		return $this->hookFlags;
	}

	public static function __callStatic(string $name, array $arguments): mixed
	{
		return call_user_func_array(
			[
				self::class,
				$name,
			], $arguments,
		);
	}
}