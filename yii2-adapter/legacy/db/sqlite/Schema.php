<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db\sqlite;

use craft\db\Connection;
use craft\db\ExpressionBuilder;
use craft\db\ExpressionInterface;

/**
 * @inheritdoc
 * @property Connection $db
 * @since 6.0.0
 */
class Schema extends \yii\db\sqlite\Schema
{
    /**
     * @var int The maximum length that objects' names can be. SQLite doesn’t limit them.
     */
    public int $maxObjectNameLength = PHP_INT_MAX;

    /**
     * @inheritdoc
     */
    public function createQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this->db, [
            'expressionBuilders' => [
                ExpressionInterface::class => ExpressionBuilder::class,
            ],
            'separator' => "\n",
        ]);
    }
}
