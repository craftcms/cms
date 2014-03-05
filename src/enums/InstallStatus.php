<?php
namespace Craft;

/**
 *
 */
abstract class InstallStatus extends BaseEnum
{
	const Success = 'success';
	const Failed  = 'failed';
	const Warning = 'warning';
}
