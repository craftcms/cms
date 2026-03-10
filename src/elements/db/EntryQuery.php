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
use craft\elements\Entry;
use craft\enums\CmsEdition;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\models\Section;
use craft\models\UserGroup;
use DateTime;
use Illuminate\Support\Collection;
use yii\base\InvalidConfigException;

/**
 * EntryQuery represents a SELECT SQL statement for entries in a way that is independent of DBMS.
 *
 * @template TKey of array-key
 * @template TElement of Entry
 * @extends ElementQuery<TKey,TElement>
 *
 * @property-write string|string[]|EntryType|null $type The entry type(s) that resulting entries must have
 * @property-write string|string[]|Section|null $section The section(s) that resulting entries must belong to
 * @property-write string|string[]|UserGroup|null $authorGroup The user group(s) that resulting entries’ authors must belong to
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @doc-path entries.md
 * @supports-structure-params
 * @supports-site-params
 * @supports-title-param
 * @supports-slug-param
 * @supports-status-param
 * @supports-uri-param
 * @supports-draft-params
 * @supports-revision-params
 * @replace {element} entry
 * @replace {elements} entries
 * @replace {twig-method} craft.entries()
 * @replace {myElement} myEntry
 * @replace {element-class} \craft\elements\Entry
 */
class EntryQuery extends ElementQuery implements NestedElementQueryInterface
{
    use NestedElementQueryTrait {
        __set as nestedTraitSet;
        cacheTags as nestedTraitCacheTags;
        fieldLayouts as nestedTraitFieldLayouts;
    }

    // General parameters
    // -------------------------------------------------------------------------

    /**
     * @var bool|null Whether to only return entries that the user has permission to view.
     * @used-by editable()
     */
    public ?bool $editable = null;

    /**
     * @var bool|null Whether to only return entries that the user has permission to save.
     * @used-by savable()
     * @since 4.4.0
     */
    public ?bool $savable = null;

    /**
     * @var mixed The section ID(s) that the resulting entries must be in.
     * ---
     * ```php
     * // fetch entries in the News section
     * $entries = \craft\elements\Entry::find()
     *     ->section('news')
     *     ->all();
     * ```
     * ```twig
     * {# fetch entries in the News section #}
     * {% set entries = craft.entries()
     *   .section('news')
     *   .all() %}
     * ```
     * @used-by section()
     * @used-by sectionId()
     */
    public mixed $sectionId = null;

    /**
     * @var mixed The entry type ID(s) that the resulting entries must have.
     * ---
     * ```php{4}
     * // fetch Article entries in the News section
     * $entries = \craft\elements\Entry::find()
     *     ->section('news')
     *     ->type('article')
     *     ->all();
     * ```
     * ```twig{4}
     * {# fetch entries in the News section #}
     * {% set entries = craft.entries()
     *   .section('news')
     *   .type('article')
     *   .all() %}
     * ```
     * @used-by EntryQuery::type()
     * @used-by typeId()
     */
    public mixed $typeId = null;

    /**
     * @var mixed The user ID(s) that the resulting entries’ authors must have.
     * @used-by authorId()
     */
    public mixed $authorId = null;

    /**
     * @var mixed The user group ID(s) that the resulting entries’ authors must be in.
     * ---
     * ```php
     * // fetch entries authored by people in the Authors group
     * $entries = \craft\elements\Entry::find()
     *     ->authorGroup('authors')
     *     ->all();
     * ```
     * ```twig
     * {# fetch entries authored by people in the Authors group #}
     * {% set entries = craft.entries()
     *   .authorGroup('authors')
     *   .all() %}
     * ```
     * @used-by authorGroup()
     * @used-by authorGroupId()
     */
    public mixed $authorGroupId = null;

    /**
     * @var mixed The Post Date that the resulting entries must have.
     * ---
     * ```php
     * // fetch entries written in 2018
     * $entries = \craft\elements\Entry::find()
     *     ->postDate(['and', '>= 2018-01-01', '< 2019-01-01'])
     *     ->all();
     * ```
     * ```twig
     * {# fetch entries written in 2018 #}
     * {% set entries = craft.entries()
     *   .postDate(['and', '>= 2018-01-01', '< 2019-01-01'])
     *   .all() %}
     * ```
     * @used-by postDate()
     */
    public mixed $postDate = null;

    /**
     * @var mixed The maximum Post Date that resulting entries can have.
     * ---
     * ```php
     * // fetch entries written before 4/4/2018
     * $entries = \craft\elements\Entry::find()
     *     ->before('2018-04-04')
     *     ->all();
     * ```
     * ```twig
     * {# fetch entries written before 4/4/2018 #}
     * {% set entries = craft.entries()
     *   .before('2018-04-04')
     *   .all() %}
     * ```
     * @used-by before()
     */
    public mixed $before = null;

    /**
     * @var mixed The minimum Post Date that resulting entries can have.
     * ---
     * ```php
     * // fetch entries written in the last 7 days
     * $entries = \craft\elements\Entry::find()
     *     ->after((new \DateTime())->modify('-7 days'))
     *     ->all();
     * ```
     * ```twig
     * {# fetch entries written in the last 7 days #}
     * {% set entries = craft.entries()
     *   .after(now|date_modify('-7 days'))
     *   .all() %}
     * ```
     * @used-by after()
     */
    public mixed $after = null;

    /**
     * @var mixed The Expiry Date that the resulting entries must have.
     * @used-by expiryDate()
     */
    public mixed $expiryDate = null;

    /**
     * @inheritdoc
     */
    protected array $defaultOrderBy = [
        'entries.postDate' => SORT_DESC,
        'elements.id' => SORT_DESC,
    ];

    /**
     * @inheritdoc
     */
    public function __construct($elementType, array $config = [])
    {
        // Default status
        if (!isset($config['status'])) {
            $config['status'] = [
                Entry::STATUS_LIVE,
            ];
        }

        parent::__construct($elementType, $config);
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'section':
                $this->section($value);
                break;
            case 'type':
                $this->type($value);
                break;
            case 'authorGroup':
                $this->authorGroup($value);
                break;
            default:
                $this->nestedTraitSet($name, $value);
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
     * @param bool|null $value The property value (defaults to true)
     * @return static self reference
     * @uses $editable
     */
    public function editable(?bool $value = true): static
    {
        $this->editable = $value;
        return $this;
    }

    /**
     * Sets the [[$savable]] property.
     *
     * @param bool|null $value The property value (defaults to true)
     * @return self self reference
     * @uses $savable
     * @since 4.4.0
     */
    public function savable(?bool $value = true): self
    {
        $this->savable = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the sections the entries belong to.
     *
     * Possible values include:
     *
     * | Value | Fetches entries…
     * | - | -
     * | `'foo'` | in a section with a handle of `foo`.
     * | `'not foo'` | not in a section with a handle of `foo`.
     * | `['foo', 'bar']` | in a section with a handle of `foo` or `bar`.
     * | `['not', 'foo', 'bar']` | not in a section with a handle of `foo` or `bar`.
     * | a [[Section|Section]] object | in a section represented by the object.
     * | `'*'` | in any section.
     *
     * ---
     *
     * ```twig
     * {# Fetch entries in the Foo section #}
     * {% set {elements-var} = {twig-method}
     *   .section('foo')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch entries in the Foo section
     * ${elements-var} = {php-method}
     *     ->section('foo')
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return static self reference
     * @uses $sectionId
     */
    public function section(mixed $value): static
    {
        // If the value is a section handle, swap it with the section
        if (is_string($value) && ($section = Craft::$app->getEntries()->getSectionByHandle($value))) {
            $value = $section;
        }

        if ($value instanceof Section) {
            // Special case for a single section, since we also want to capture the structure ID
            $this->sectionId = [$value->id];
            if ($value->structureId) {
                $this->structureId = $value->structureId;
            } else {
                $this->withStructure = false;
            }
        } elseif ($value === '*') {
            $this->sectionId = Craft::$app->getEntries()->getAllSectionIds();
        } elseif (Db::normalizeParam($value, function($item) {
            if (is_string($item)) {
                $item = Craft::$app->getEntries()->getSectionByHandle($item);
            }
            return $item instanceof Section ? $item->id : null;
        })) {
            $this->sectionId = $value;
        } else {
            $this->sectionId = (new Query())
                ->select(['id'])
                ->from([Table::SECTIONS])
                ->where(Db::parseParam('handle', $value))
                ->column();
        }

        return $this;
    }

    /**
     * Narrows the query results based on the sections the entries belong to, per the sections’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches entries…
     * | - | -
     * | `1` | in a section with an ID of 1.
     * | `'not 1'` | not in a section with an ID of 1.
     * | `[1, 2]` | in a section with an ID of 1 or 2.
     * | `['not', 1, 2]` | not in a section with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch entries in the section with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .sectionId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch entries in the section with an ID of 1
     * ${elements-var} = {php-method}
     *     ->sectionId(1)
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return static self reference
     * @uses $sectionId
     */
    public function sectionId(mixed $value): static
    {
        $this->sectionId = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the entries’ entry types.
     *
     * Possible values include:
     *
     * | Value | Fetches entries…
     * | - | -
     * | `'foo'` | of a type with a handle of `foo`.
     * | `'not foo'` | not of a type with a handle of `foo`.
     * | `['foo', 'bar']` | of a type with a handle of `foo` or `bar`.
     * | `['not', 'foo', 'bar']` | not of a type with a handle of `foo` or `bar`.
     * | an [[EntryType|EntryType]] object | of a type represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch entries in the Foo section with a Bar entry type #}
     * {% set {elements-var} = {twig-method}
     *   .section('foo')
     *   .type('bar')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch entries in the Foo section with a Bar entry type
     * ${elements-var} = {php-method}
     *     ->section('foo')
     *     ->type('bar')
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return static self reference
     * @uses $typeId
     */
    public function type(mixed $value): static
    {
        if (Db::normalizeParam($value, function($item) {
            if (is_string($item)) {
                $item = Craft::$app->getEntries()->getEntryTypeByHandle($item);
            }
            return $item instanceof EntryType ? $item->id : null;
        })) {
            $this->typeId = $value;
        } else {
            $this->typeId = (new Query())
                ->select(['id'])
                ->from([Table::ENTRYTYPES])
                ->where(Db::parseParam('handle', $value))
                ->column();
        }

        return $this;
    }

    /**
     * Narrows the query results based on the entries’ entry types, per the types’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches entries…
     * | - | -
     * | `1` | of a type with an ID of 1.
     * | `'not 1'` | not of a type with an ID of 1.
     * | `[1, 2]` | of a type with an ID of 1 or 2.
     * | `['not', 1, 2]` | not of a type with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch entries of the entry type with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .typeId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch entries of the entry type with an ID of 1
     * ${elements-var} = {php-method}
     *     ->typeId(1)
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return static self reference
     * @uses $typeId
     */
    public function typeId(mixed $value): static
    {
        $this->typeId = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the entries’ author ID(s).
     *
     * Possible values include:
     *
     * | Value | Fetches entries…
     * | - | -
     * | `1` | with an author with an ID of 1.
     * | `'not 1'` | not with an author with an ID of 1.
     * | `[1, 2]` | with an author with an ID of 1 or 2.
     * | `['and', 1, 2]` |  with authors with IDs of 1 and 2.
     * | `['not', 1, 2]` | not with an author with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch entries with an author with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .authorId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch entries with an author with an ID of 1
     * ${elements-var} = {php-method}
     *     ->authorId(1)
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return static self reference
     * @uses $authorId
     */
    public function authorId(mixed $value): static
    {
        $this->authorId = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the user group the entries’ authors belong to.
     *
     * Possible values include:
     *
     * | Value | Fetches entries…
     * | - | -
     * | `'foo'` | with an author in a group with a handle of `foo`.
     * | `'not foo'` | not with an author in a group with a handle of `foo`.
     * | `['foo', 'bar']` | with an author in a group with a handle of `foo` or `bar`.
     * | `['not', 'foo', 'bar']` | not with an author in a group with a handle of `foo` or `bar`.
     * | a [[UserGroup|UserGroup]] object | with an author in a group represented by the object.
     * | an array of [[UserGroup|UserGroup]] objects | with an author in a group represented by the objects.
     *
     * ---
     *
     * ```twig
     * {# Fetch entries with an author in the Foo user group #}
     * {% set {elements-var} = {twig-method}
     *   .authorGroup('foo')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch entries with an author in the Foo user group
     * ${elements-var} = {php-method}
     *     ->authorGroup('foo')
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return static self reference
     * @uses $authorGroupId
     */
    public function authorGroup(mixed $value): static
    {
        if ($value instanceof UserGroup) {
            $this->authorGroupId = $value->id;
            return $this;
        }

        if (ArrayHelper::isTraversable($value)) {
            $collection = Collection::make($value);
            if ($collection->every(fn($v) => $v instanceof UserGroup)) {
                $this->authorGroupId = $collection->map(fn(UserGroup $g) => $g->id)->all();
                return $this;
            }
        }

        if ($value !== null) {
            $this->authorGroupId = (new Query())
                ->select(['id'])
                ->from([Table::USERGROUPS])
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->authorGroupId = null;
        }

        return $this;
    }

    /**
     * Narrows the query results based on the user group the entries’ authors belong to, per the groups’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches entries…
     * | - | -
     * | `1` | with an author in a group with an ID of 1.
     * | `'not 1'` | not with an author in a group with an ID of 1.
     * | `[1, 2]` | with an author in a group with an ID of 1 or 2.
     * | `['not', 1, 2]` | not with an author in a group with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch entries with an author in a group with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .authorGroupId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch entries with an author in a group with an ID of 1
     * ${elements-var} = {php-method}
     *     ->authorGroupId(1)
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return static self reference
     * @uses $authorGroupId
     */
    public function authorGroupId(mixed $value): static
    {
        $this->authorGroupId = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the entries’ post dates.
     *
     * Possible values include:
     *
     * | Value | Fetches entries…
     * | - | -
     * | `'>= 2018-04-01'` | that were posted on or after 2018-04-01.
     * | `'< 2018-05-01'` | that were posted before 2018-05-01.
     * | `['and', '>= 2018-04-04', '< 2018-05-01']` | that were posted between 2018-04-01 and 2018-05-01.
     * | `now`/`today`/`tomorrow`/`yesterday` | that were posted at midnight of the specified relative date.
     *
     * ---
     *
     * ```twig
     * {# Fetch entries posted last month #}
     * {% set start = date('first day of last month')|atom %}
     * {% set end = date('first day of this month')|atom %}
     *
     * {% set {elements-var} = {twig-method}
     *   .postDate(['and', ">= #{start}", "< #{end}"])
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch entries posted last month
     * $start = (new \DateTime('first day of last month'))->format(\DateTime::ATOM);
     * $end = (new \DateTime('first day of this month'))->format(\DateTime::ATOM);
     *
     * ${elements-var} = {php-method}
     *     ->postDate(['and', ">= {$start}", "< {$end}"])
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return static self reference
     * @uses $postDate
     */
    public function postDate(mixed $value): static
    {
        $this->postDate = $value;
        return $this;
    }

    /**
     * Narrows the query results to only entries that were posted before a certain date.
     *
     * Possible values include:
     *
     * | Value | Fetches entries…
     * | - | -
     * | `'2018-04-01'` | that were posted before 2018-04-01.
     * | a [[\DateTime|DateTime]] object | that were posted before the date represented by the object.
     * | `now`/`today`/`tomorrow`/`yesterday` | that were posted before midnight of specified relative date.
     *
     * ---
     *
     * ```twig
     * {# Fetch entries posted before this month #}
     * {% set firstDayOfMonth = date('first day of this month') %}
     *
     * {% set {elements-var} = {twig-method}
     *   .before(firstDayOfMonth)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch entries posted before this month
     * $firstDayOfMonth = new \DateTime('first day of this month');
     *
     * ${elements-var} = {php-method}
     *     ->before($firstDayOfMonth)
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return static self reference
     * @uses $before
     */
    public function before(mixed $value): static
    {
        $this->before = $value;
        return $this;
    }

    /**
     * Narrows the query results to only entries that were posted on or after a certain date.
     *
     * Possible values include:
     *
     * | Value | Fetches entries…
     * | - | -
     * | `'2018-04-01'` | that were posted on or after 2018-04-01.
     * | a [[\DateTime|DateTime]] object | that were posted on or after the date represented by the object.
     * | `now`/`today`/`tomorrow`/`yesterday` | that were posted on or after midnight of the specified relative date.
     *
     * ---
     *
     * ```twig
     * {# Fetch entries posted this month #}
     * {% set firstDayOfMonth = date('first day of this month') %}
     *
     * {% set {elements-var} = {twig-method}
     *   .after(firstDayOfMonth)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch entries posted this month
     * $firstDayOfMonth = new \DateTime('first day of this month');
     *
     * ${elements-var} = {php-method}
     *     ->after($firstDayOfMonth)
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return static self reference
     * @uses $after
     */
    public function after(mixed $value): static
    {
        $this->after = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the entries’ expiry dates.
     *
     * Possible values include:
     *
     * | Value | Fetches entries…
     * | - | -
     * | `':empty:'` | that don’t have an expiry date.
     * | `':notempty:'` | that have an expiry date.
     * | `'>= 2020-04-01'` | that will expire on or after 2020-04-01.
     * | `'< 2020-05-01'` | that will expire before 2020-05-01
     * | `['and', '>= 2020-04-04', '< 2020-05-01']` | that will expire between 2020-04-01 and 2020-05-01.
     * | `now`/`today`/`tomorrow`/`yesterday` | that expire at midnight of the specified relative date.
     *
     * ---
     *
     * ```twig
     * {# Fetch entries expiring this month #}
     * {% set nextMonth = date('first day of next month')|atom %}
     *
     * {% set {elements-var} = {twig-method}
     *   .expiryDate("< #{nextMonth}")
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch entries expiring this month
     * $nextMonth = (new \DateTime('first day of next month'))->format(\DateTime::ATOM);
     *
     * ${elements-var} = {php-method}
     *     ->expiryDate("< {$nextMonth}")
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return static self reference
     * @uses $expiryDate
     */
    public function expiryDate(mixed $value): static
    {
        $this->expiryDate = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the entries’ statuses.
     *
     * Possible values include:
     *
     * | Value | Fetches entries…
     * | - | -
     * | `'live'` _(default)_ | that are live.
     * | `'pending'` | that are pending (enabled with a Post Date in the future).
     * | `'expired'` | that are expired (enabled with an Expiry Date in the past).
     * | `'disabled'` | that are disabled.
     * | `['live', 'pending']` | that are live or pending.
     * | `['not', 'live', 'pending']` | that are not live or pending.
     *
     * ---
     *
     * ```twig
     * {# Fetch disabled entries #}
     * {% set {elements-var} = {twig-method}
     *   .status('disabled')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch disabled entries
     * ${elements-var} = {element-class}::find()
     *     ->status('disabled')
     *     ->all();
     * ```
     */
    public function status(array|string|null $value): static
    {
        /** @var static */
        return parent::status($value);
    }

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        if (!parent::beforePrepare()) {
            return false;
        }

        $this->_normalizeSectionId();
        $this->_normalizeTypeId();

        // See if 'section', 'type', or 'authorGroup' were set to invalid handles
        if ($this->sectionId === [] || $this->typeId === [] || $this->authorGroupId === []) {
            return false;
        }

        $this->joinElementTable(Table::ENTRIES);

        $this->query->addSelect([
            'entries.sectionId',
            'entries.fieldId',
            'entries.primaryOwnerId',
            'entries.typeId',
            'entries.postDate',
            'entries.expiryDate',
        ]);

        // todo: update after the next breakpoint
        if (
            Craft::$app->getConfig()->getGeneral()->staticStatuses &&
            Craft::$app->getDb()->columnExists(Table::ENTRIES, 'status')
        ) {
            $this->query->addSelect(['entries.status']);
        }

        $this->_applySectionIdParam();
        $this->applyNestedElementParams('entries.fieldId', 'entries.primaryOwnerId');

        if ($this->postDate) {
            $this->subQuery->andWhere(Db::parseDateParam('entries.postDate', $this->postDate));
        } else {
            if ($this->before) {
                $this->subQuery->andWhere(Db::parseDateParam('entries.postDate', $this->before, '<'));
            }
            if ($this->after) {
                $this->subQuery->andWhere(Db::parseDateParam('entries.postDate', $this->after, '>='));
            }
        }

        if ($this->expiryDate) {
            $this->subQuery->andWhere(Db::parseDateParam('entries.expiryDate', $this->expiryDate));
        }

        if ($this->typeId) {
            $this->subQuery->andWhere(['entries.typeId' => $this->typeId]);
        }

        if (Craft::$app->edition !== CmsEdition::Solo) {
            if ($this->authorId) {
                // Checking multiple authors?
                if (
                    is_array($this->authorId) &&
                    is_string(reset($this->authorId)) &&
                    strtolower(reset($this->authorId)) === 'and'
                ) {
                    $authorIdChecks = array_slice($this->authorId, 1);
                } else {
                    $authorIdChecks = [$this->authorId];
                }

                foreach ($authorIdChecks as $i => $authorIdCheck) {
                    if (
                        is_array($authorIdCheck) &&
                        is_string(reset($authorIdCheck)) &&
                        strtolower(reset($authorIdCheck)) === 'not'
                    ) {
                        $authorIdOperator = 'not exists';
                        array_shift($authorIdCheck);
                        if (empty($authorIdCheck)) {
                            continue;
                        }
                    } else {
                        $authorIdOperator = 'exists';
                    }

                    $this->subQuery->andWhere([
                        $authorIdOperator, (new Query())
                            ->from(['entries_authors' => Table::ENTRIES_AUTHORS])
                            ->where('[[entries.id]] = [[entries_authors.entryId]]')
                            ->andWhere(Db::parseNumericParam('authorId', $authorIdCheck)),
                    ]);
                }
            }

            if ($this->authorGroupId) {
                $this->subQuery->andWhere(['exists', (new Query())
                    ->from(['entries_authors' => Table::ENTRIES_AUTHORS])
                    ->innerJoin(['usergroups_users' => Table::USERGROUPS_USERS], '[[usergroups_users.userId]] = [[entries_authors.authorId]]')
                    ->where('[[entries.id]] = [[entries_authors.entryId]]')
                    ->andWhere(Db::parseNumericParam('usergroups_users.groupId', $this->authorGroupId)),
                ]);
            }
        }

        $this->_applyAuthParam($this->editable, 'viewEntries', 'viewPeerEntries', 'viewPeerEntryDrafts');
        $this->_applyAuthParam($this->savable, 'saveEntries', 'savePeerEntries', 'savePeerEntryDrafts');
        $this->_applyRefParam();

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function statusCondition(string $status): mixed
    {
        if (
            in_array($status, [Entry::STATUS_LIVE, Entry::STATUS_PENDING, Entry::STATUS_EXPIRED]) &&
            Craft::$app->getConfig()->getGeneral()->staticStatuses
        ) {
            return [
                'elements.enabled' => true,
                'elements_sites.enabled' => true,
                'entries.status' => $status,
            ];
        }

        // Always consider “now” to be the current time @ 59 seconds into the minute.
        // This makes entry queries more cacheable, since they only change once every minute (https://github.com/craftcms/cms/issues/5389),
        // while not excluding any entries that may have just been published in the past minute (https://github.com/craftcms/cms/issues/7853).
        $now = new DateTime();
        $now->setTime((int)$now->format('H'), (int)$now->format('i'), 59);
        $currentTimeDb = Db::prepareDateForDb($now);

        return match ($status) {
            Entry::STATUS_LIVE => [
                'and',
                [
                    'elements.enabled' => true,
                    'elements_sites.enabled' => true,
                ],
                ['<=', 'entries.postDate', $currentTimeDb],
                [
                    'or',
                    ['entries.expiryDate' => null],
                    ['>', 'entries.expiryDate', $currentTimeDb],
                ],
            ],
            Entry::STATUS_PENDING => [
                'and',
                [
                    'elements.enabled' => true,
                    'elements_sites.enabled' => true,
                ],
                ['>', 'entries.postDate', $currentTimeDb],
            ],
            Entry::STATUS_EXPIRED => [
                'and',
                [
                    'elements.enabled' => true,
                    'elements_sites.enabled' => true,
                ],
                ['not', ['entries.expiryDate' => null]],
                ['<=', 'entries.expiryDate', $currentTimeDb],
            ],
            default => parent::statusCondition($status),
        };
    }

    /**
     * @param bool|null $value
     * @param string $permissionPrefix
     * @param string $peerPermissionPrefix
     * @param string $peerDraftPermissionPrefix
     * @throws QueryAbortedException
     */
    private function _applyAuthParam(
        ?bool $value,
        string $permissionPrefix,
        string $peerPermissionPrefix,
        string $peerDraftPermissionPrefix,
    ): void {
        if ($value === null) {
            return;
        }

        $user = Craft::$app->getUser()->getIdentity();

        if (!$user) {
            throw new QueryAbortedException();
        }

        $sections = Craft::$app->getEntries()->getAllSections();

        if (empty($sections)) {
            return;
        }

        $sectionConditions = [];
        $fullyAuthorizedSectionIds = [];

        foreach ($sections as $section) {
            if (!$user->can("$permissionPrefix:$section->uid")) {
                continue;
            }

            $excludePeerEntries = $section->type !== Section::TYPE_SINGLE && !$user->can("$peerPermissionPrefix:$section->uid");
            $excludePeerDrafts = $this->drafts !== false && !$user->can("$peerDraftPermissionPrefix:$section->uid");

            if ($excludePeerEntries || $excludePeerDrafts) {
                $sectionCondition = [
                    'and',
                    ['entries.sectionId' => $section->id],
                ];
                if ($excludePeerEntries) {
                    $sectionCondition[] = ['exists', (new Query())
                        ->from(['entries_authors' => Table::ENTRIES_AUTHORS])
                        ->where('[[entries_authors.entryId]] = [[entries.id]]')
                        ->andWhere(['entries_authors.authorId' => $user->id]), ];
                }
                if ($excludePeerDrafts) {
                    $sectionCondition[] = [
                        'or',
                        ['elements.draftId' => null],
                        ['drafts.creatorId' => $user->id],
                    ];
                }
                $sectionConditions[] = $sectionCondition;
            } else {
                $fullyAuthorizedSectionIds[] = $section->id;
            }
        }

        if (!empty($fullyAuthorizedSectionIds)) {
            if (count($fullyAuthorizedSectionIds) === count($sections)) {
                // They have access to everything
                if (!$value) {
                    throw new QueryAbortedException();
                }
                return;
            }

            $sectionConditions[] = ['entries.sectionId' => $fullyAuthorizedSectionIds];
        }

        if (empty($sectionConditions)) {
            // They don't have access to anything
            if ($value) {
                throw new QueryAbortedException();
            }
            return;
        }

        $condition = ['or', ...$sectionConditions];

        if (!$value) {
            $condition = ['not', $condition];
        }

        $this->subQuery->andWhere($condition);
    }

    /**
     * Normalizes the typeId param to an array of IDs or null
     *
     * @throws InvalidConfigException
     */
    private function _normalizeTypeId(): void
    {
        if (empty($this->typeId)) {
            $this->typeId = is_array($this->typeId) ? [] : null;
        } elseif (is_numeric($this->typeId)) {
            $this->typeId = [$this->typeId];
        } elseif (!is_array($this->typeId) || !ArrayHelper::isNumeric($this->typeId)) {
            $this->typeId = (new Query())
                ->select(['id'])
                ->from([Table::ENTRYTYPES])
                ->where(Db::parseNumericParam('id', $this->typeId))
                ->column();
        }
    }

    /**
     * Applies the 'sectionId' param to the query being prepared.
     */
    private function _applySectionIdParam(): void
    {
        if ($this->sectionId) {
            $this->subQuery->andWhere(['entries.sectionId' => $this->sectionId]);

            // Should we set the structureId param?
            if (
                $this->withStructure !== false &&
                !isset($this->structureId) &&
                count($this->sectionId) === 1
            ) {
                $section = Craft::$app->getEntries()->getSectionById(reset($this->sectionId));
                if ($section && $section->type === Section::TYPE_STRUCTURE) {
                    $this->structureId = $section->structureId;
                } else {
                    $this->withStructure = false;
                }
            }
        }
    }

    /**
     * Normalizes the sectionId param to an array of IDs or null
     */
    private function _normalizeSectionId(): void
    {
        if (empty($this->sectionId)) {
            $this->sectionId = is_array($this->sectionId) ? [] : null;
        } elseif (is_numeric($this->sectionId)) {
            $this->sectionId = [$this->sectionId];
        } elseif (!is_array($this->sectionId) || !ArrayHelper::isNumeric($this->sectionId)) {
            $this->sectionId = (new Query())
                ->select(['id'])
                ->from([Table::SECTIONS])
                ->where(Db::parseNumericParam('id', $this->sectionId))
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

        $joinSections = false;
        $condition = ['or'];

        foreach ($refs as $ref) {
            $parts = array_filter(explode('/', $ref));

            if (!empty($parts)) {
                if (count($parts) == 1) {
                    $condition[] = Db::parseParam('elements_sites.slug', $parts[0]);
                } else {
                    $condition[] = [
                        'and',
                        Db::parseParam('sections.handle', $parts[0]),
                        Db::parseParam('elements_sites.slug', $parts[1]),
                    ];
                    $joinSections = true;
                }
            }
        }

        $this->subQuery->andWhere($condition);

        if ($joinSections) {
            $this->subQuery->innerJoin(['sections' => Table::SECTIONS], '[[sections.id]] = [[entries.sectionId]]');
        }
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    protected function cacheTags(): array
    {
        $tags = [];

        // If the type is set, go with that instead of the section
        if ($this->typeId) {
            foreach ($this->typeId as $typeId) {
                $tags[] = "entryType:$typeId";
            }
        } elseif ($this->sectionId) {
            foreach ($this->sectionId as $sectionId) {
                $tags[] = "section:$sectionId";
            }
        }

        array_push($tags, ...$this->nestedTraitCacheTags());

        return $tags;
    }

    /**
     * @inheritdoc
     */
    protected function fieldLayouts(): array
    {
        $this->_normalizeTypeId();
        $this->_normalizeSectionId();

        if ($this->typeId || $this->sectionId) {
            $fieldLayouts = [];
            $sectionsService = Craft::$app->getEntries();
            if ($this->typeId) {
                foreach ($this->typeId as $entryTypeId) {
                    $entryType = $sectionsService->getEntryTypeById($entryTypeId);
                    if ($entryType) {
                        $fieldLayouts[] = $entryType->getFieldLayout();
                    }
                }
            } else {
                foreach ($this->sectionId as $sectionId) {
                    $section = $sectionsService->getSectionById($sectionId);
                    if ($section) {
                        foreach ($section->getEntryTypes() as $entryType) {
                            $fieldLayouts[] = $entryType->getFieldLayout();
                        }
                    }
                }
            }
            return $fieldLayouts;
        }

        return $this->nestedTraitFieldLayouts();
    }
}
