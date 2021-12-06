<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\enums;

/**
 * The PeriodType class is an abstract class that defines the various time period lengths that are available in Craft.
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0.0
 */
abstract class PeriodType
{
    public const Seconds = 'seconds';
    public const Minutes = 'minutes';
    public const Hours = 'hours';
    public const Days = 'days';
    public const Weeks = 'weeks';
    public const Months = 'months';
    public const Years = 'years';
}
