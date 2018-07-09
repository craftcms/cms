<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

use Craft;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\elements\Category;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\models\CategoryGroup;
use yii\db\Connection;

/**
 * CategoryQuery represents a SELECT SQL statement for categories in a way that is independent of DBMS.
 *
 * @property string|string[]|CategoryGroup $group The handle(s) of the category group(s) that resulting categories must belong to.
 * @method Category[]|array all($db = null)
 * @method Category|array|null one($db = null)
 * @method Category|array|null nth(int $n, Connection $db = null)
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 * @supports-structure-params
 */
class CategoryQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    // General parameters
    // -------------------------------------------------------------------------

    /**
     * @var bool Whether to only return categories that the user has permission to edit.
     * @used-by editable()
     */
    public $editable = false;

    /**
     * @var int|int[]|null The category group ID(s) that the resulting categories must be in.
     * ---
     * ```php
     * // fetch categories in the Topics group
     * $categories = \craft\elements\Category::find()
     *     ->group('topics')
     *     ->all();
     * ```
     * ```twig
     * {# fetch categories in the Topics group #}
     * {% set categories = craft.categories()
     *     .group('topics')
     *     .all() %}
     * ```
     * @used-by group()
     * @used-by groupId()
     */
    public $groupId;

    // Public Methods
    // =========================================================================

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
     * @inheritdoc
     */
    public function init()
    {
        if ($this->withStructure === null) {
            $this->withStructure = true;
        }

        parent::init();
    }

    /**
     * Sets the [[$editable]] property.
     *
     * @param bool $value The property value (defaults to true)
     * @return static self reference
     * @uses $editable
     */
    public function editable(bool $value = true)
    {
        $this->editable = $value;
        return $this;
    }

    /**
     * Sets the [[$groupId]] property based on a given category group(s)’s handle(s).
     *
     * @param string|string[]|CategoryGroup|null $value The property value
     * @return static self reference
     * @uses $groupId
     */
    public function group($value)
    {
        if ($value instanceof CategoryGroup) {
            $this->structureId = ($value->structureId ?: false);
            $this->groupId = $value->id;
        } else if ($value !== null) {
            $this->groupId = (new Query())
                ->select(['id'])
                ->from('{{%categorygroups}}')
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->groupId = null;
        }

        return $this;
    }

    /**
     * Sets the [[$groupId]] property.
     *
     * @param int|int[]|null $value The property value
     * @return static self reference
     * @uses $groupId
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

        $this->joinElementTable('categories');

        $this->query->select([
            'categories.groupId',
        ]);

        $this->_applyEditableParam();
        $this->_applyGroupIdParam();
        $this->_applyRefParam();

        return parent::beforePrepare();
    }

    // Private Methods
    // =========================================================================

    /**
     * Applies the 'editable' param to the query being prepared.
     *
     * @throws QueryAbortedException
     */
    private function _applyEditableParam()
    {
        if ($this->editable) {
            // Limit the query to only the category groups the user has permission to edit
            $this->subQuery->andWhere([
                'categories.groupId' => Craft::$app->getCategories()->getEditableGroupIds()
            ]);
        }
    }

    /**
     * Applies the 'groupId' param to the query being prepared.
     */
    private function _applyGroupIdParam()
    {
        if ($this->groupId) {
            // Should we set the structureId param?
            if ($this->structureId === null && (!is_array($this->groupId) || count($this->groupId) === 1)) {
                $structureId = (new Query())
                    ->select(['structureId'])
                    ->from(['{{%categorygroups}}'])
                    ->where(Db::parseParam('id', $this->groupId))
                    ->scalar();
                $this->structureId = $structureId ? (int)$structureId : false;
            }

            $this->subQuery->andWhere(Db::parseParam('categories.groupId', $this->groupId));
        }
    }

    /**
     * Applies the 'ref' param to the query being prepared.
     */
    private function _applyRefParam()
    {
        if (!$this->ref) {
            return;
        }

        $refs = $this->ref;
        if (!is_array($refs)) {
            $refs = is_string($refs) ? StringHelper::split($refs) : [$refs];
        }

        $condition = ['or'];
        $joinCategoryGroups = false;

        foreach ($refs as $ref) {
            $parts = array_filter(explode('/', $ref));

            if (!empty($parts)) {
                if (count($parts) == 1) {
                    $condition[] = Db::parseParam('elements_sites.slug', $parts[0]);
                } else {
                    $condition[] = [
                        'and',
                        Db::parseParam('categorygroups.handle', $parts[0]),
                        Db::parseParam('elements_sites.slug', $parts[1])
                    ];
                    $joinCategoryGroups = true;
                }
            }
        }

        $this->subQuery->andWhere($condition);

        if ($joinCategoryGroups) {
            $this->subQuery->innerJoin('{{%categorygroups}} categorygroups', '[[categorygroups.id]] = [[categories.groupId]]');
        }
    }
}
