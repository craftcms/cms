<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

use craft\db\Query;
use craft\elements\Tag;
use craft\helpers\Db;
use craft\models\TagGroup;
use yii\db\Connection;

/**
 * TagQuery represents a SELECT SQL statement for tags in a way that is independent of DBMS.
 *
 * @property string|string[]|TagGroup $group The handle(s) of the tag group(s) that resulting tags must belong to.
 * @method Tag[]|array all($db = null)
 * @method Tag|array|null one($db = null)
 * @method Tag|array|null nth(int $n, Connection $db = null)
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class TagQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    // General parameters
    // -------------------------------------------------------------------------

    /**
     * @var int|int[]|null The tag group ID(s) that the resulting tags must be in.
     */
    public $groupId;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($elementType, array $config = [])
    {
        // Default orderBy
        if (!isset($config['orderBy'])) {
            $config['orderBy'] = 'content.title';
        }

        parent::__construct($elementType, $config);
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if ($name === 'group') {
            $this->group($value);
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * Sets the [[groupId]] property based on a given tag group(s)’s handle(s).
     *
     * @param string|string[]|TagGroup|null $value The property value
     * @return static self reference
     */
    public function group($value)
    {
        if ($value instanceof TagGroup) {
            $this->groupId = $value->id;
        } else if ($value !== null) {
            $this->groupId = (new Query())
                ->select(['id'])
                ->from(['{{%taggroups}}'])
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->groupId = null;
        }

        return $this;
    }

    /**
     * Sets the [[groupId]] property.
     *
     * @param int|int[]|null $value The property value
     * @return static self reference
     */
    public function groupId($value)
    {
        $this->groupId = $value;
        return $this;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        // See if 'group' was set to an invalid handle
        if ($this->groupId === []) {
            return false;
        }

        $this->joinElementTable('tags');

        $this->query->select([
            'tags.groupId',
        ]);

        if ($this->groupId) {
            $this->subQuery->andWhere(Db::parseParam('tags.groupId', $this->groupId));
        }

        return parent::beforePrepare();
    }
}
