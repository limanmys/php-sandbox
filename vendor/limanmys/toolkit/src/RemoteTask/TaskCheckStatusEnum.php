<?php
namespace Liman\Toolkit\RemoteTask;

abstract class TaskCheckStatusEnum
{
	const Pending = 'pending';
	const Failed = 'failed';
	const Success = 'success';
}
