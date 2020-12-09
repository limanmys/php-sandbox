<?php
namespace Liman\Toolkit\Shell;

class CommandEngine implements ICommandEngine
{
	/**
	 * Run command.
	 *
	 * @param  string  $command
	 * @return string
	 */
	public static function run($command)
	{
		return runCommand($command);
	}

	/**
	 * Manipulated sudo command to passing password to sudo binary.
	 *
	 * @return string
	 */
	public static function sudo()
	{
		return sudo();
	}
}
