<?php
namespace Craft;

/**
 * Class LicenseKeyStatus
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
	////////////////////
	// CONSTANTS
	////////////////////

	const Valid            = 'Valid';
	const Invalid          = 'Invalid';
	const Missing          = 'Missing';
	const Unverified       = 'Unverified';
	const MismatchedDomain = 'MismatchedDomain';
}
