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
use craft\db\Table;
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
 * @supports-site-params
 * @supports-enabledforsite-param
 * @supports-title-param
 * @supports-slug-param
 * @supports-uri-param
 * @supports-status-param
 * @replace {element} category
 * @replace {elements} categories
 * @replace {twig-method} craft.categories()
 * @replace {myElement} myCategory
 * @replace {element-class} \craft\elements\Category
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
     * Narrows the query results based on the category groups the categories belong to.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'foo'` | in a group with a handle of `foo`.
     * | `'not foo'` | not in a group with a handle of `foo`.
     * | `['foo', 'bar']` | in a group with a handle of `foo` or `bar`.
     * | `['not', 'foo', 'bar']` | not in a group with a handle of `foo` or `bar`.
     * | a [[CategoryGroup|CategoryGroup]] object | in a group represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} in the Foo group #}
     * {% set {elements-var} = {twig-method}
     *     .group('foo')
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} in the Foo group
     * ${elements-var} = {php-method}
     *     ->group('foo')
     *     ->all();
     * ```
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
                ->from(Table::CATEGORYGROUPS)
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->groupId = null;
        }

        return $this;
    }

    /**
     * Narrows the query results based on the category groups the categories belong to, per the groups’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | in a group with an ID of 1.
     * | `'not 1'` | not in a group with an ID of 1.
     * | `[1, 2]` | in a group with an ID of 1 or 2.
     * | `['not', 1, 2]` | not in a group with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} in the group with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *     .groupId(1)
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} in the group with an ID of 1
     * ${elements-var} = {php-method}
     *     ->groupId(1)
     *     ->all();
     * ```
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
                    ->from([Table::CATEGORYGROUPS])
                    ->where(Db::parseParam('id', $this->groupId))
                    ->scalar();
                $this->structureId = (int)$structureId ?: false;
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
