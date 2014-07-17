<?php
namespace Craft;

/**
 * Class TaskStatus
 *
 * @abstract
 * @package craft.app.enums
 */
class TaskStatus
{
	const Pending = 'pending';
	const Running = 'running';
	const Error   = 'error';
}
