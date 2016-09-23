<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\elements\db;

use Craft;
use craft\app\db\Query;
use craft\app\db\QueryAbortedException;
use craft\app\elements\Category;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\Db;
use craft\app\models\CategoryGroup;

/**
 * CategoryQuery represents a SELECT SQL statement for categories in a way that is independent of DBMS.
 *
 * @property string|string[]|CategoryGroup $group The handle(s) of the category group(s) that resulting categories must belong to.
 *
 * @method Category[]|array all($db = null)
 * @method Category|array|null one($db = null)
 * @method Category|array|null nth($n, $db = null)
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class CategoryQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    // General parameters
    // -------------------------------------------------------------------------

    /**
     * @var boolean Whether to only return categories that the user has permission to edit.
     */
    public $editable;

    /**
     * @var integer|integer[] The category group ID(s) that the resulting categories must be in.
     */
    public $groupId;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'group': {
                $this->group($value);
                break;
            }
            default: {
                parent::__set($name, $value);
            }
        }
    }

    /**
     * Sets the [[editable]] property.
     *
     * @param boolean $value The property value (defaults to true)
     *
     * @return $this self reference
     */
    public function editable($value = true)
    {
        $this->editable = $value;

        return $this;
    }

    /**
     * Sets the [[groupId]] property based on a given category group(s)’s handle(s).
     *
     * @param string|string[]|CategoryGroup $value The property value
     *
     * @return $this self reference
     */
    public function group($value)
    {
        if ($value instanceof CategoryGroup) {
            $this->structureId = ($value->structureId ?: false);
            $this->groupId = $value->id;
        } else {
            $query = new Query();
            $this->groupId = $query
                ->select('id')
                ->from('{{%categorygroups}}')
                ->where(Db::parseParam('handle', $value, $query->params))
                ->column();
        }

        return $this;
    }

    /**
     * Sets the [[groupId]] property.
     *
     * @param integer|integer[] $value The property value
     *
     * @return $this self reference
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
    protected function beforePrepare()
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
            $editableGroupIds = Craft::$app->getCategories()->getEditableGroupIds();
            $this->subQuery->andWhere([
                'in',
                'categories.groupId',
                $editableGroupIds
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
                $query = new Query();
                $this->structureId = $query
                    ->select('structureId')
                    ->from('{{%categorygroups}}')
                    ->where(Db::parseParam('id', $this->groupId, $query->params))
                    ->scalar();
            }

            $this->subQuery->andWhere(Db::parseParam('categories.groupId', $this->groupId, $this->subQuery->params));
        }
    }

    /**
     * Applies the 'ref' param to the query being prepared.
     */
    private function _applyRefParam()
    {
        if ($this->ref) {
            $joinCategoryGroups = false;
            $refs = ArrayHelper::toArray($this->ref);
            $conditionals = [];

            foreach ($refs as $ref) {
                $parts = array_filter(explode('/', $ref));

                if ($parts) {
                    if (count($parts) == 1) {
                        $conditionals[] = Db::parseParam('elements_i18n.slug', $parts[0], $this->subQuery->params);
                    } else {
                        $conditionals[] = [
                            'and',
                            Db::parseParam('categorygroups.handle', $parts[0], $this->subQuery->params),
                            Db::parseParam('elements_i18n.slug', $parts[1], $this->subQuery->params)
                        ];
                        $joinCategoryGroups = true;
                    }
                }
            }

            if ($conditionals) {
                if (count($conditionals) == 1) {
                    $this->subQuery->andWhere($conditionals[0]);
                } else {
                    array_unshift($conditionals, 'or');
                    $this->subQuery->andWhere($conditionals);
                }

                if ($joinCategoryGroups) {
                    $this->subQuery->innerJoin('{{%categorygroups}} categorygroups', 'categorygroups.id = categories.groupId');
                }
            }
        }
    }
}
