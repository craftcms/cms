<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\volumes;

use craft\app\base\Volume;
use craft\app\base\InvalidComponentInterface;
use craft\app\base\InvalidComponentTrait;

/**
 * InvalidSource represents a volume with an invalid class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class InvalidVolume extends Volume implements InvalidComponentInterface
{
    // Traits
    // =========================================================================

    use InvalidComponentTrait;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createAdapter()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getRootUrl()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getRootPath()
    {
        return null;
    }
}
