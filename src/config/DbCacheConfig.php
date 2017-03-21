<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\config;

use yii\base\Object;

/**
 * DbCache config class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class DbCacheConfig extends Object
{
    // Properties
    // =========================================================================

    /**
     * @var int The probability (parts per million) that garbage collection (GC) should be performed when storing a piece of data
     * in the cache. Defaults to 100, meaning 0.01% chance.
     */
    public $gcProbability = 100;
    /**
     * @var string The name of the cache table in the database.  Note that Craft will add the table prefix from your
     * `config/db.php` file.
     */
    public $cacheTableName = '{{%cache}}';
}
