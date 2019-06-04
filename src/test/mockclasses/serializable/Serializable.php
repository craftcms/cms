<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\test\mockclasses\serializable;

use craft\base\Serializable as SerializableInterface;

/**
 * Class Serializable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.2
 */
class Serializable implements SerializableInterface
{
    // Public Methods
    // =========================================================================

    /**
     * @return mixed|string
     */
    public function serialize()
    {
        return 'Serialized data';
    }
}
