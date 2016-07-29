<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\volumes;

use craft\app\base\Volume;
use craft\app\base\MissingComponentInterface;
use craft\app\base\MissingComponentTrait;

/**
 * InvalidSource represents a volume with an invalid class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class MissingVolume extends Volume implements MissingComponentInterface
{
    // Traits
    // =========================================================================

    use MissingComponentTrait;

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
