<?php
namespace Liman\Toolkit;

class Formatter
{
	/**
	 * Text string
	 *
	 * @var string
	 */
	private $text;

	/**
	 * Text attributes
	 *
	 * @var array
	 */
	private $attributes;

	/**
	 * Initialize class.
	 *
	 * @param  string  $text
	 * @param  array   $attributes
	 * @return void
	 */
	public function __construct(string $text, array $attributes = [])
	{
		$this->text = $text;
		$this->attributes = $attributes;
	}

	/**
	 * Process formatter.
	 *
	 * @return string
	 */
	public function process()
	{
		foreach ($this->attributes as $attribute => $value) {
			$this->text = str_replace(
				"@{:$attribute}",
				$this->clean($value),
				$this->text
			);
			$this->text = str_replace(
				"{:$attribute}",
				$this->cleanWithoutQuotes($value),
				$this->text
			);
			$this->text = str_replace(":$attribute:", $value, $this->text);
		}
		return $this->text;
	}

	/**
	 * Clean attributes without quotes.
	 *
	 * @param  string  $value
	 * @return string
	 */
	private function cleanWithoutQuotes($value)
	{
		return preg_replace(
			'/^(\'(.*)\'|"(.*)")$/',
			'$2$3',
			$this->clean($value)
		);
	}

	/**
	 * Clean attributes.
	 *
	 * @param  string  $value
	 * @return string
	 */
	private function clean($value)
	{
		return escapeshellcmd(escapeshellarg($value));
	}

	/**
	 * Handle static call
	 *
	 * @param  string  $text
	 * @param  array   $attributes
	 * @return self
	 */
	public static function run(string $text, array $attributes = [])
	{
		return (new self($text, $attributes))->process();
	}
}
