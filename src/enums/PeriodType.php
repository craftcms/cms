<?php
namespace Craft;

/**
 * The PeriodType class is an abstract class that defines the various time period lengths that are available in Craft.
 *
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.enums
 * @since     2.0
 */
abstract class PeriodType extends BaseEnum
{
	// Constants
	// =========================================================================

	const Seconds = 'seconds';
	const Minutes = 'minutes';
	const Hours   = 'hours';
	const Days    = 'days';
	const Weeks   = 'weeks';
	const Months  = 'months';
	const Years   = 'years';
}
