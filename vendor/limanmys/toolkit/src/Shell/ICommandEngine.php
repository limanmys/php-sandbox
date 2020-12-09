<?php
namespace Liman\Toolkit\Shell;

interface ICommandEngine
{
	/**
	 * Run command.
	 *
	 * @param  string  $command
	 * @return string
	 */

	public static function run($command);

	/**
	 * Manipulated sudo command to passing password to sudo binary.
	 *
	 * @return string
	 */
	public static function sudo();
}
