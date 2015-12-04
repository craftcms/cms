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
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.enums
 * @since     1.0
 */
abstract class LicenseKeyStatus extends BaseEnum
{
	// Constants
	// =========================================================================

	const Valid = 'valid';
	const Invalid = 'invalid';
	const Mismatched = 'mismatched';
	const Unknown = 'unknown';
}
