<?php
namespace Liman\Toolkit\RemoteTask;

use Exception;
use Liman\Toolkit\Formatter;
use ReflectionClass;

class TaskManager
{
	/**
	 * Get specific task
	 *
	 * @return Task
	 */
	public static function get(string $taskName, array $attributes = [])
	{
		$task = Formatter::run('App\\Tasks\\{:taskName}', [
			'taskName' => $taskName
		]);
		if (!class_exists($task)) {
			$task = $taskName;
		}
		$class = new ReflectionClass($task);
		if (!$class->isSubclassOf(Task::class)) {
			throw new Exception('Task must be subclass of Task');
		}
		return new $task($attributes);
	}
}
