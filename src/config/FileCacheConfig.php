<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\config;

use yii\base\Object;

/**
 * FileCache config class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class FileCacheConfig extends Object
{
    // Properties
    // =========================================================================

    /**
     * @var string The file system path to use for caching. If empty, Craft will default to `storage/runtime/cache/`.
     */
    public $cachePath = '@runtime/cache';
    /**
     * @var int The probability (parts per million) that garbage collection (GC) should be performed when storing a piece of data
     * in the cache. Defaults to 100, meaning 0.01% chance.
     */
    public $gcProbability = 100;
}
