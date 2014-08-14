<?php
namespace Craft;

/**
 * The LicenseKeyStatus class is an abstract class that defines all of the license key status states that are available
 * in Craft.
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
abstract class LicenseKeyStatus extends BaseEnum
{
	// Constants
	// =========================================================================

	const Valid            = 'Valid';
	const Invalid          = 'Invalid';
	const Missing          = 'Missing';
	const Unverified       = 'Unverified';
	const MismatchedDomain = 'MismatchedDomain';
}
