<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

use Craft;
use craft\db\QueryAbortedException;
use craft\elements\GlobalSet;
use craft\helpers\Db;
use yii\db\Connection;

/**
 * GlobalSetQuery represents a SELECT SQL statement for global sets in a way that is independent of DBMS.
 *
 * @method GlobalSet[]|array all($db = null)
 * @method GlobalSet|array|null one($db = null)
 * @method GlobalSet|array|null nth(int $n, Connection $db = null)
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 * @supports-site-params
 * @replace {element} global set
 * @replace {elements} global sets
 * @replace {twig-method} craft.globalSets()
 * @replace {myElement} myGlobalSet
 * @replace {element-class} \craft\elements\GlobalSet
 */
class GlobalSetQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected $defaultOrderBy = ['globalsets.name' => SORT_ASC];

    // General parameters
    // -------------------------------------------------------------------------

    /**
     * @var bool Whether to only return global sets that the user has permission to edit.
     * @used-by editable()
     */
    public $editable = false;

    /**
     * @var string|string[]|null The handle(s) that the resulting global sets must have.
     * @used-by handle()
     */
    public $handle;

    // Public Methods
    // =========================================================================

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
     * Narrows the query results based on the global sets’ handles.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'foo'` | with a handle of `foo`.
     * | `'not foo'` | not with a handle of `foo`.
     * | `['foo', 'bar']` | with a handle of `foo` or `bar`.
     * | `['not', 'foo', 'bar']` | not with a handle of `foo` or `bar`.
     *
     * ---
     *
     * ```twig
     * {# Fetch the {element} with a handle of 'foo' #}
     * {% set {element-var} = {twig-method}
     *     .handle('foo')
     *     .one() %}
     * ```
     *
     * ```php
     * // Fetch the {element} with a handle of 'foo'
     * ${element-var} = {php-method}
     *     ->handle('foo')
     *     ->one();
     * ```
     *
     * @param string|string[]|null $value The property value
     * @return static self reference
     * @uses $handle
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
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('globalsets');

        $this->query->select([
            'globalsets.name',
            'globalsets.handle',
        ]);

        if ($this->handle) {
            $this->subQuery->andWhere(Db::parseParam('globalsets.handle', $this->handle));
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
            $this->subQuery->andWhere(['elements.id' => $editableSetIds]);
        }
    }
}
