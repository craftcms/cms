<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\enums;

/**
 * The DateRangeType class is an abstract class that defines the various date ranges that are available in Craft.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 */
abstract class DateRangeType
{
    public const Today = 'today';
    public const ThisWeek = 'thisWeek';
    public const ThisMonth = 'thisMonth';
    public const ThisYear = 'thisYear';
    public const Past7Days = 'past7Days';
    public const Past30Days = 'past30Days';
    public const Past90Days = 'past90Days';
    public const PastYear = 'pastYear';
    public const Before = 'before';
    public const After = 'after';
    public const Range = 'range';
}
