<?php
namespace Craft;

/**
 * Class InstallStatus
 *
 * @package craft.app.enums
 */
abstract class InstallStatus extends BaseEnum
{
	const Success = 'success';
	const Failed  = 'failed';
	const Warning = 'warning';
}
