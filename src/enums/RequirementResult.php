<?php
namespace Craft;

/**
 * Class RequirementResult
 *
 * @package craft.app.enums
 */
abstract class RequirementResult extends BaseEnum
{
	const Success = 'success';
	const Failed  = 'failed';
	const Warning = 'warning';
}
