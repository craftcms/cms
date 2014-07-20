<?php
namespace Craft;

/**
 * Class PeriodType
 *
 * @abstract
 * @package craft.app.enums
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
