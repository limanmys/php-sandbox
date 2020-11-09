<?php
namespace Liman\Toolkit;

use Illuminate\Validation;
use Illuminate\Translation;
use Illuminate\Filesystem\Filesystem;

class Validator
{
	/**
	 * Validation factory.
	 *
	 * @var Validation\Factory
	 */
	private $factory;

	/**
	 * Initialize class.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->factory = new Validation\Factory($this->loadTranslator());
	}

	/**
	 * Initialize class.
	 *
	 * @return Translation\Translator
	 */
	protected function loadTranslator()
	{
		global $limanData;
		$filesystem = new Filesystem();
		$loader = new Translation\FileLoader($filesystem, getPath('/lang'));
		$loader->addNamespace('lang', getPath('/lang'));
		$loader->load('en', 'validation', 'lang');
		return new Translation\Translator($loader, $limanData['locale']);
	}

	/**
	 * Dynamically pass methods to validation factory.
	 *
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return call_user_func_array([$this->factory, $method], $parameters);
	}
}
