<?php
namespace Liman\Toolkit\RemoteTask;

use Liman\Toolkit\Formatter;
use Liman\Toolkit\Shell\Command;

abstract class Task
{
	/**
	 * Task description
	 *
	 * @var bool
	 */
	protected $description;

	/**
	 * Command to run on background
	 *
	 * @var bool
	 */
	protected $command;

	/**
	 * Addition command to check if process success or not
	 *
	 * @var bool
	 */
	protected $checkCommand = null;

	/**
	 * Is sudo required?
	 *
	 * @var bool
	 */
	protected $sudoRequired = false;

	/**
	 * Remote log file path
	 *
	 * @var string
	 */
	protected $logFile;

	/**
	 * Ps aux grep pattern to control process
	 *
	 * @var string
	 */
	protected $control;

	/**
	 * Command attributes
	 *
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * Run the command on background
	 *
	 * @return TaskRunStatus
	 */
	public function run()
	{
		$this->before();
		$command = Formatter::run($this->command, $this->attributes);
		if ($this->checkFirst()->getStatus() == TaskCheckStatusEnum::Pending) {
			return new TaskRunStatus(
				TaskRunStatusEnum::Conflict,
				'',
				$this->description
			);
		}
		$status = (bool) $this->command(
			"bash -c '(echo @{:command} | base64 -d | bash) > @{:logFile} 2>&1 & disown && echo 1 || echo 0'",
			[
				'command' => base64_encode($command),
				'logFile' => $this->logFile
			]
		);
		$processOutput = $this->command(
			'cat @{:logFile} && truncate -s 0 @{:logFile} 2> /dev/null',
			[
				'logFile' => $this->logFile
			]
		);
		$processStatus = $status
			? TaskRunStatusEnum::Started
			: TaskRunStatusEnum::Failed;
		return new TaskRunStatus(
			$processStatus,
			$processOutput,
			$this->description
		);
	}

	/**
	 * Check first command status
	 *
	 * @return TaskCheckStatus
	 */
	public function checkFirst()
	{
		$status = (bool) $this->command(
			'ps aux | grep @{:control} | grep -v grep 2>/dev/null 1>/dev/null && echo 1 || echo 0',
			['control' => $this->control]
		);
		$processStatus = $status
			? TaskCheckStatusEnum::Pending
			: TaskCheckStatusEnum::Success;
		return new TaskCheckStatus($processStatus, '');
	}

	/**
	 * Check command status
	 *
	 * @return TaskCheckStatus
	 */
	public function check()
	{
		$status = (bool) $this->command(
			'ps aux | grep @{:control} | grep -v grep 2>/dev/null 1>/dev/null && echo 1 || echo 0',
			['control' => $this->control]
		);
		$processStatus = $status
			? TaskCheckStatusEnum::Pending
			: TaskCheckStatusEnum::Success;
		if (
			$this->checkCommand &&
			$processStatus == TaskCheckStatusEnum::Success
		) {
			$status = (bool) $this->command(
				'bash -c @{:checkCommand} 2>/dev/null 1>/dev/null && echo 1 || echo 0',
				['checkCommand' => $this->checkCommand]
			);
			$processStatus = $status
				? TaskCheckStatusEnum::Success
				: TaskCheckStatusEnum::Failed;
		}
		$processOutput = $this->command(
			'cat @{:logFile} && truncate -s 0 @{:logFile} 2> /dev/null',
			['logFile' => $this->logFile]
		);
		if ($processStatus == TaskCheckStatusEnum::Success) {
			$this->after();
		}
		return new TaskCheckStatus($processStatus, $processOutput);
	}

	/**
	 * Command helper
	 *
	 * @return string
	 */
	private function command(string $command, array $attributes = [])
	{
		if ($this->sudoRequired) {
			return Command::runSudo($command, $attributes);
		}
		return Command::run($command, $attributes);
	}

	/**
	 * Before
	 *
	 * @return void
	 */
	protected function before()
	{
	}

	/**
	 * After
	 *
	 * @return void
	 */
	protected function after()
	{
	}
}
