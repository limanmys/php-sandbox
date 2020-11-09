<?php
namespace Liman\Toolkit\RemoteTask;

class TaskCheckStatus
{
	/**
	 * Status
	 *
	 * @var TaskCheckStatusEnum
	 */
	public $status;

	/**
	 * Output
	 *
	 * @var string
	 */
	public $output;

	/**
	 * Initialize class.
	 *
	 * @param  string  $status
	 * @param  string  $output
	 * @return void
	 */
	public function __construct(string $status, string $output)
	{
		$this->status = $status;
		$this->output = $output;
	}

	/**
	 * Get status
	 *
	 * @var TaskCheckStatusEnum
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * Get output
	 *
	 * @var string
	 */
	public function getOutput()
	{
		return $this->output;
	}
}
