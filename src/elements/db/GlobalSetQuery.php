<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\elements\db;

use Craft;
use craft\app\db\QueryAbortedException;
use craft\app\elements\GlobalSet;
use craft\app\helpers\Db;

/**
 * GlobalSetQuery represents a SELECT SQL statement for global sets in a way that is independent of DBMS.
 *
 * @method GlobalSet[]|array all($db = null)
 * @method GlobalSet|array|null one($db = null)
 * @method GlobalSet|array|null nth($n, $db = null)
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class GlobalSetQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    // General parameters
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public $orderBy = 'name';

    /**
     * @var boolean Whether to only return global sets that the user has permission to edit.
     */
    public $editable;

    /**
     * @var string|string[] The handle(s) that the resulting global sets must have.
     */
    public $handle;

    // Public Methods
    // =========================================================================

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
     * Sets the [[handle]] property.
     *
     * @param string|string[] $value The property value
     *
     * @return $this self reference
     */
    public function handle($value)
    {
        $this->handle = $value;

        return $this;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function beforePrepare()
    {
        $this->joinElementTable('globalsets');

        $this->query->select([
            'globalsets.name',
            'globalsets.handle',
            'globalsets.fieldLayoutId',
        ]);

        if ($this->handle) {
            $this->subQuery->andWhere(Db::parseParam('globalsets.handle', $this->handle, $this->subQuery->params));
        }

        $this->_applyEditableParam();

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
            // Limit the query to only the global sets the user has permission to edit
            $editableSetIds = Craft::$app->getGlobals()->getEditableSetIds();
            $this->subQuery->andWhere(['in', 'elements.id', $editableSetIds]);
        }
    }
}
