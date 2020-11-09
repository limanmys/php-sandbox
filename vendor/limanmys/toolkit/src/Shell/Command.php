<?php
namespace Liman\Toolkit\Shell;

use Liman\Toolkit\Formatter;
use ReflectionClass;
use Exception;

class Command
{
	/**
	 * The binded engine to execute command and sudo handling.
	 *
	 * @var string
	 */
	private static $engine = CommandEngine::class;

	/**
	 * Run command on specified engine.
	 *
	 * @param  string  $command
	 * @param  array   $attributes
	 * @return string
	 */
	public static function run($command, $attributes = [])
	{
		return trim(self::$engine::run(Formatter::run($command, $attributes)));
	}

	/**
	 * Run command on specified engine as root.
	 *
	 * @param  string  $command
	 * @param  array   $attributes
	 * @return string
	 */
	public static function runSudo($command, $attributes = [])
	{
		return self::run(self::$engine::sudo() . $command, $attributes);
	}

	/**
	 * Bind a engine class.
	 *
	 * @param  string  $engine
	 * @return void
	 *
	 * @throws \Exception
	 */
	public static function bindEngine($engine)
	{
		$class = new ReflectionClass($engine);
		if (!$class->implementsInterface(ICommandEngine::class)) {
			throw new Exception('Engine must implement ICommandEngine');
		}
		self::$engine = $engine;
	}

	/**
	 * Get the current engine.
	 *
	 * @return string
	 */
	public static function getEngine()
	{
		return self::$engine;
	}

	/**
	 * Bind the default engine.
	 *
	 * @return void
	 */
	public static function bindDefaultEngine()
	{
		self::$engine = CommandEngine::class;
	}
}
