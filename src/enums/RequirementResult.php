<?php
namespace Craft;

/**
 * Class RequirementResult
 *
 * @abstract
 * @package craft.app.enums
 */
abstract class RequirementResult extends BaseEnum
{
	const Success = 'success';
	const Failed  = 'failed';
	const Warning = 'warning';
}
