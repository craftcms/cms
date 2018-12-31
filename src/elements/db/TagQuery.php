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
 * @supports-site-params
 * @supports-title-param
 * @supports-uri-param
 * @replace {element} tag
 * @replace {elements} tags
 * @replace {twig-method} craft.tags()
 * @replace {myElement} myTag
 * @replace {element-class} \craft\elements\Tag
 */
class TagQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected $defaultOrderBy = ['content.title' => SORT_ASC];

    // General parameters
    // -------------------------------------------------------------------------

    /**
     * @var int|int[]|null The tag group ID(s) that the resulting tags must be in.
     * ---
     * ```php
     * // fetch tags in the Topics group
     * $tags = \craft\elements\Tag::find()
     *     ->group('topics')
     *     ->all();
     * ```
     * ```twig
     * {# fetch tags in the Topics group #}
     * {% set tags = craft.tags()
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
     * Narrows the query results based on the tag groups the tags belong to.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'foo'` | in a group with a handle of `foo`.
     * | `'not foo'` | not in a group with a handle of `foo`.
     * | `['foo', 'bar']` | in a group with a handle of `foo` or `bar`.
     * | `['not', 'foo', 'bar']` | not in a group with a handle of `foo` or `bar`.
     * | a [[TagGroup|TagGroup]] object | in a group represented by the object.
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
     * @param string|string[]|TagGroup|null $value The property value
     * @return static self reference
     * @uses $groupId
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
     * Narrows the query results based on the tag groups the tags belong to, per the groups’ IDs.
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
