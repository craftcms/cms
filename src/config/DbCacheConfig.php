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
     * `config/db.php` file, but you will need to manually create the cache table before using this cache method.
     *
     * MySQL:
     *
     * php```
     * DROP TABLE IF EXISTS craft_cache;
     *
     * CREATE TABLE craft_cache (
     *     id char(128) NOT NULL PRIMARY KEY,
     *     expire int(11),
     *     data BLOB,
     *     dateCreated datetime NOT NULL,
     *     dateUpdated datetime NOT NULL,
     *     uid char(36) NOT NULL DEFAULT 0
     * );
     * ```
     *
     * PostgreSQL:
     *
     * php```
     * DROP TABLE IF EXISTS craft_cache;
     *
     * CREATE TABLE craft_cache (
     *     id char(128) NOT NULL PRIMARY KEY,
     *     expire int4,
     *     data BYTEA,
     *     dateCreated timestamp NOT NULL,
     *     dateUpdated timestamp NOT NULL,
     *     uid char(36) NOT NULL DEFAULT '0'::bpchar
     * );
     * ```
     *
     * Note that these examples use the default `craft/config/db.php` config setting of `craft`.
     *
     * If you have changed that config setting, or you change the $cacheTableName property value,
     * you will want to adjust the examples accordingly.
     */
    public $cacheTableName = 'cache';
}
