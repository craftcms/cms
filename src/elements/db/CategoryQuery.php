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
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\models\CategoryGroup;
use yii\db\Connection;

/**
 * CategoryQuery represents a SELECT SQL statement for categories in a way that is independent of DBMS.
 *
 * @property-write string|string[]|CategoryGroup|null $group The category group(s) that resulting categories must belong to
 * @method Category[]|array all($db = null)
 * @method Category|array|null one($db = null)
 * @method Category|array|null nth(int $n, ?Connection $db = null)
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @doc-path categories.md
 * @supports-structure-params
 * @supports-site-params
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
    // General parameters
    // -------------------------------------------------------------------------

    /**
     * @var bool Whether to only return categories that the user has permission to edit.
     * @used-by editable()
     */
    public bool $editable = false;

    /**
     * @var mixed The category group ID(s) that the resulting categories must be in.
     * @used-by group()
     * @used-by groupId()
     */
    public mixed $groupId = null;

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
    public function init(): void
    {
        if (!isset($this->withStructure)) {
            $this->withStructure = true;
        }

        parent::init();
    }

    /**
     * Sets the [[$editable]] property.
     *
     * @param bool $value The property value (defaults to true)
     * @return self self reference
     * @uses $editable
     */
    public function editable(bool $value = true): self
    {
        $this->editable = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the category groups the categories belong to.
     *
     * Possible values include:
     *
     * | Value | Fetches categories…
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
     * {# Fetch categories in the Foo group #}
     * {% set {elements-var} = {twig-method}
     *   .group('foo')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch categories in the Foo group
     * ${elements-var} = {php-method}
     *     ->group('foo')
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return self self reference
     * @uses $groupId
     */
    public function group(mixed $value): self
    {
        if ($value instanceof CategoryGroup) {
            // Special case for a single category group, since we also want to capture the structure ID
            $this->structureId = ($value->structureId ?: false);
            $this->groupId = [$value->id];
        } elseif (Db::normalizeParam($value, function($item) {
            if (is_string($item)) {
                $item = Craft::$app->getCategories()->getGroupByHandle($item);
            }
            return $item instanceof CategoryGroup ? $item->id : null;
        })) {
            $this->groupId = $value;
        } else {
            $this->groupId = (new Query())
                ->select(['id'])
                ->from(Table::CATEGORYGROUPS)
                ->where(Db::parseParam('handle', $value))
                ->column();
        }

        return $this;
    }

    /**
     * Narrows the query results based on the category groups the categories belong to, per the groups’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches categories…
     * | - | -
     * | `1` | in a group with an ID of 1.
     * | `'not 1'` | not in a group with an ID of 1.
     * | `[1, 2]` | in a group with an ID of 1 or 2.
     * | `['not', 1, 2]` | not in a group with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch categories in the group with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .groupId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch categories in the group with an ID of 1
     * ${elements-var} = {php-method}
     *     ->groupId(1)
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return self self reference
     * @uses $groupId
     */
    public function groupId(mixed $value): self
    {
        $this->groupId = $value;
        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->_normalizeGroupId();

        // See if 'group' was set to an invalid handle
        if ($this->groupId === []) {
            return false;
        }

        $this->joinElementTable(Table::CATEGORIES);

        $this->query->select([
            'categories.groupId',
        ]);

        $this->_applyEditableParam();
        $this->_applyGroupIdParam();
        $this->_applyRefParam();

        return parent::beforePrepare();
    }

    /**
     * Applies the 'editable' param to the query being prepared.
     *
     * @throws QueryAbortedException
     */
    private function _applyEditableParam(): void
    {
        if ($this->editable) {
            // Limit the query to only the category groups the user has permission to edit
            $this->subQuery->andWhere([
                'categories.groupId' => Craft::$app->getCategories()->getEditableGroupIds(),
            ]);
        }
    }

    /**
     * Applies the 'groupId' param to the query being prepared.
     */
    private function _applyGroupIdParam(): void
    {
        if ($this->groupId) {
            $this->subQuery->andWhere(['categories.groupId' => $this->groupId]);

            // Should we set the structureId param?
            if (!isset($this->structureId) && count($this->groupId) === 1) {
                $structureId = (new Query())
                    ->select(['structureId'])
                    ->from([Table::CATEGORYGROUPS])
                    ->where(Db::parseNumericParam('id', $this->groupId))
                    ->scalar();
                $this->structureId = (int)$structureId ?: false;
            }
        }
    }

    /**
     * Normalizes the groupId param to an array of IDs or null
     */
    private function _normalizeGroupId(): void
    {
        if (empty($this->groupId)) {
            $this->groupId = is_array($this->groupId) ? [] : null;
        } elseif (is_numeric($this->groupId)) {
            $this->groupId = [$this->groupId];
        } elseif (!is_array($this->groupId) || !ArrayHelper::isNumeric($this->groupId)) {
            $this->groupId = (new Query())
                ->select(['id'])
                ->from([Table::CATEGORYGROUPS])
                ->where(Db::parseNumericParam('id', $this->groupId))
                ->column();
        }
    }

    /**
     * Applies the 'ref' param to the query being prepared.
     */
    private function _applyRefParam(): void
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
                        Db::parseParam('elements_sites.slug', $parts[1]),
                    ];
                    $joinCategoryGroups = true;
                }
            }
        }

        $this->subQuery->andWhere($condition);

        if ($joinCategoryGroups) {
            $this->subQuery->innerJoin(['categorygroups' => Table::CATEGORYGROUPS], '[[categorygroups.id]] = [[categories.groupId]]');
        }
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    protected function cacheTags(): array
    {
        $tags = [];
        if ($this->groupId) {
            foreach ($this->groupId as $groupId) {
                $tags[] = "group:$groupId";
            }
        }
        return $tags;
    }
}
