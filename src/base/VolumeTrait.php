<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\base;

/**
 * VolumeTrait
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
trait VolumeTrait
{
    // Properties
    // =========================================================================

    /**
     * @var string Name
     */
    public $name;

    /**
     * @var string Handle
     */
    public $handle;

    /**
     * @var string The source’s URL
     */
    public $url;

    /**
     * @var integer Sort order
     */
    public $sortOrder;

    /**
     * @var integer Field layout ID
     */
    public $fieldLayoutId;
}
