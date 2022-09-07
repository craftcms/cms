<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use Craft;
use craft\db\mysql\Schema as MysqlSchema;
use craft\db\pgsql\Schema as PgsqlSchema;
use craft\helpers\Db;
use DateTime;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * SchemaTrait
 *
 * @mixin MysqlSchema
 * @mixin PgsqlSchema
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 */
trait SchemaTrait
{
    /**
     * @see getServerVersion()
     * @see setServerVersion()
     */
    private ?string $version = null;

    /**
     * Returns the database server version.
     *
     * @return string
     */
    public function getServerVersion(): string
    {
        return $this->version ?? parent::getServerVersion();
    }

    /**
     * Sets the database server version.
     *
     * @param string $version
     */
    public function setServerVersion(string $version): void
    {
        $this->version = $version;
    }
}
