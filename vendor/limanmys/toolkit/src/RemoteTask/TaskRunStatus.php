<?php
namespace Liman\Toolkit\RemoteTask;

class TaskRunStatus
{
	/**
	 * Status
	 *
	 * @var TaskRunStatusEnum
	 */
	public $status;

	/**
	 * Output
	 *
	 * @var string
	 */
	public $output;

	/**
	 * Description
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Initialize class.
	 *
	 * @param  string  $status
	 * @param  string  $output
	 * @param  string  $description
	 * @return void
	 */
	public function __construct(
		string $status,
		string $output,
		string $description
	) {
		$this->status = $status;
		$this->output = $output;
		$this->description = $description;
	}

	/**
	 * Get status
	 *
	 * @var TaskRunStatusEnum
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

	/**
	 * Get description
	 *
	 * @var string
	 */
	public function getDescription()
	{
		return $this->description;
	}
}
