<?php
namespace Liman\Toolkit\RemoteTask;

abstract class TaskRunStatusEnum
{
	const Started = 'started';
	const Failed = 'failed';
	const Conflict = 'conflict';
}
