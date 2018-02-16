<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * VolumeTrait
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
trait VolumeTrait
{
    // Properties
    // =========================================================================

    /**
     * @var string|null Name
     */
    public $name;

    /**
     * @var string|null Handle
     */
    public $handle;

    /**
     * @var bool|null Whether the volume has a public URL
     */
    public $hasUrls;

    /**
     * @var string|null The volume’s URL
     */
    public $url;

    /**
     * @var int|null Sort order
     */
    public $sortOrder;

    /**
     * @var int|null Field layout ID
     */
    public $fieldLayoutId;
}
