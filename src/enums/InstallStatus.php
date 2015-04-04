<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\enums;

/**
 * The InstallStatus class is an abstract class that defines all of the install status states that are available in Craft during installation.
 *
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class InstallStatus extends BaseEnum
{
	// Constants
	// =========================================================================

	const Success = 'success';
	const Failed  = 'failed';
	const Warning = 'warning';
}
