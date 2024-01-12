<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use Illuminate\Support\Collection;
use yii\db\Connection as YiiConnection;

/**
 * Active Query class.
 *
 * @property-read string $alias The table alias for [[modelClass]].
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.13
 */
class ActiveQuery extends \yii\db\ActiveQuery
{
    /**
     * Executes the query and returns all results as a collection.
     *
     * @param YiiConnection|null $db The database connection used to generate the SQL statement.
     * If null, the DB connection returned by [[modelClass]] will be used.
     * @return Collection A collection of the resulting records.
     * @since 4.3.0
     */
    public function collect(?YiiConnection $db = null): Collection
    {
        return Collection::make($this->all($db));
    }

    /**
     * Returns the table alias for [[modelClass]].
     *
     * @return string
     */
    public function getAlias(): string
    {
        [, $alias] = $this->getTableNameAndAlias();
        return $alias;
    }
}
