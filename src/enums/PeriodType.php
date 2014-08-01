<?php
namespace Craft;

/**
 * Class PeriodType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.enums
 * @since     2.0
 */
abstract class PeriodType extends BaseEnum
{
	const Seconds = 'seconds';
	const Minutes = 'minutes';
	const Hours   = 'hours';
	const Days    = 'days';
	const Weeks   = 'weeks';
	const Months  = 'months';
	const Years   = 'years';
}
