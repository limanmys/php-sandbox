<?php
namespace Liman\Toolkit\OS;

use Liman\Toolkit\Shell\Command;
use Dotenv\Dotenv;

class DistroInfo
{
	/**
	 * Distro id
	 *
	 * @var string
	 */
	public $distroID;

	/**
	 * Version id
	 *
	 * @var string
	 */
	public $versionID;

	/**
	 * Base os
	 *
	 * @var string
	 */
	public $base;

	/**
	 * Pretty name
	 *
	 * @var string
	 */
	public $pretty;

	/**
	 * Major version
	 *
	 * @var string
	 */
	public $majorVersion;

	/**
	 * Minor version
	 *
	 * @var string
	 */
	public $minorVersion;

	/**
	 * Slug
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * Major Slug
	 *
	 * @var string
	 */
	public $majorSlug;

	/**
	 * Call parseEnv function when it's initialized.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->parseEnv();
	}

	/**
	 * Read and parse os-release file.
	 *
	 * @return void
	 */
	private function parseEnv()
	{
		$release = Command::run('cat /etc/os-release | grep =');
		$env = Dotenv::parse($release);
		$this->distroID = strtolower(trim($env['ID']));
		$this->versionID = strtolower(trim($env['VERSION_ID']));
		$this->base = strtolower(trim($env['ID_LIKE']));
		$this->pretty = trim($env['PRETTY_NAME']);
		$this->parseVersion();
		$this->parseSlug();
	}

	/**
	 * Parse os version.
	 *
	 * @return void
	 */
	private function parseVersion()
	{
		$version = explode('.', $this->versionID);
		$this->majorVersion = $version[0];
		$this->minorVersion = $version[1];
	}

	/**
	 * Parse os slug.
	 *
	 * @return void
	 */
	private function parseSlug()
	{
		$this->slug =
			$this->distroID . $this->majorVersion . $this->minorVersion;
		$this->majorSlug = $this->distroID . $this->majorVersion;
	}
}
