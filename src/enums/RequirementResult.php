<?php
namespace Craft;

/**
 * The RequirementResult class is an abstract class that defines the different requirement result status results
 * available in Craft for when the requirement checker runs.
 *
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.enums
 * @since     1.0
 */
abstract class RequirementResult extends BaseEnum
{
	// Constants
	// =========================================================================

	const Success = 'success';
	const Failed  = 'failed';
	const Warning = 'warning';
}
