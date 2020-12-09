<?php
namespace Liman\Toolkit\Shell;

use Liman\Toolkit\Formatter;
use Exception;

class SSHEngine implements ICommandEngine
{
	/**
	 * Remote machine's hostname.
	 *
	 * @var string
	 */
	private static $hostname;

	/**
	 * Remote machine's username.
	 *
	 * @var string
	 */
	private static $username;

	/**
	 * Remote machine's password.
	 *
	 * @var string
	 */
	private static $password;

	/**
	 * Class initialize identifier.
	 *
	 * @var bool
	 */
	private static $initialized = false;

	/**
	 * Init engine to connect host.
	 *
	 * @param  string  $hostname
	 * @param  string  $username
	 * @param  string  $password
	 * @return void
	 */
	public static function init($hostname, $username, $password)
	{
		self::$hostname = $hostname;
		self::$username = $username;
		self::$password = $password;
		self::$initialized = true;
	}

	/**
	 * Run command.
	 *
	 * @param  string  $command
	 * @return string
	 */
	public static function run($command)
	{
		if (!self::$initialized) {
			throw new Exception(
				'The SSHEngine class must ve initialized with init() function.'
			);
		}
		return executeOutsideCommand(
			'ssh',
			self::$username,
			self::$password,
			self::$hostname,
			22,
			$command
		);
	}

	/**
	 * Manipulated sudo command to passing password to sudo binary.
	 *
	 * @return string
	 */
	public static function sudo()
	{
		return Formatter::run(
			'echo @{:password} | base64 -d | sudo -S -p " " id 2>/dev/null 1>/dev/null; sudo ',
			['password' => base64_encode(self::$password . "\n")]
		);
	}
}
