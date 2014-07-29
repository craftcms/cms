<?php
namespace Craft;

/**
 * Class RequirementResult
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.enums
 * @since     1.0
 */
abstract class RequirementResult extends BaseEnum
{
	const Success = 'success';
	const Failed  = 'failed';
	const Warning = 'warning';
}
