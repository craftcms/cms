<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Countable;

/**
 * Batchable defines the common interface to be implemented by classes that
 * provide items which can be counted and accessed in slices.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
interface Batchable extends Countable
{
    /**
     * Returns a slice of the items
     *
     * @param int $offset
     * @param int $limit
     * @return iterable
     */
    public function getSlice(int $offset, int $limit): iterable;
}
