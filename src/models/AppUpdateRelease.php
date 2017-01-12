<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\models;

/**
 * Stores the info for a Craft update release.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class AppUpdateRelease extends UpdateRelease
{
    // Properties
    // =========================================================================

    /**
     * @var string|null Type
     */
    public $type;

    /**
     * @var bool Manual
     */
    public $manual = false;

    /**
     * @var bool Breakpoint
     */
    public $breakpoint = false;
}
