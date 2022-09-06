<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\enums;

/**
 * The DateRange class is an abstract class that defines the various date ranges that are available in Craft.
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 */
abstract class DateRange
{
    public const Today = 'today';
    public const ThisWeek = 'thisWeek';
    public const ThisMonth = 'thisMonth';
    public const ThisYear = 'thisYear';
    public const Past7Days = 'past7Days';
    public const Past30Days = 'past30Days';
    public const Past90Days = 'past90Days';
    public const PastYear = 'pastYear';
}
