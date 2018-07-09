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
use craft\elements\Entry;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\models\Section;
use craft\models\UserGroup;
use yii\db\Connection;

/**
 * EntryQuery represents a SELECT SQL statement for entries in a way that is independent of DBMS.
 *
 * @property string|string[]|Section $section The handle(s) of the section(s) that resulting entries must belong to.
 * @property string|string[]|EntryType $type The handle(s) of the entry type(s) that resulting entries must have.
 * @property string|string[]|UserGroup $authorGroup The handle(s) of the user group(s) that resulting entries’ authors must belong to.
 * @method Entry[]|array all($db = null)
 * @method Entry|array|null one($db = null)
 * @method Entry|array|null nth(int $n, Connection $db = null)
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 * @supports-structure-params
 */
class EntryQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    // General parameters
    // -------------------------------------------------------------------------

    /**
     * @var bool Whether to only return entries that the user has permission to edit.
     * @used-by editable()
     */
    public $editable = false;

    /**
     * @var int|int[]|null The section ID(s) that the resulting entries must be in.
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
     *     .section('news')
     *     .all() %}
     * ```
     * @used-by section()
     * @used-by sectionId()
     */
    public $sectionId;

    /**
     * @var int|int[]|null The entry type ID(s) that the resulting entries must have.
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
     *     .section('news')
     *     .type('article')
     *     .all() %}
     * ```
     * @used-by EntryQuery::type()
     * @used-by typeId()
     */
    public $typeId;

    /**
     * @var int|int[]|null The user ID(s) that the resulting entries’ authors must have.
     * @used-by authorId()
     */
    public $authorId;

    /**
     * @var int|int[]|null The user group ID(s) that the resulting entries’ authors must be in.
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
     *     .authorGroup('authors')
     *     .all() %}
     * ```
     * @used-by authorGroup()
     * @used-by authorGroupId()
     */
    public $authorGroupId;

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
     *     .postDate(['and', '>= 2018-01-01', '< 2019-01-01'])
     *     .all() %}
     * ```
     * @used-by postDate()
     */
    public $postDate;

    /**
     * @var string|array|\DateTime The maximum Post Date that resulting entries can have.
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
     *     .before('2018-04-04')
     *     .all() %}
     * ```
     * @used-by before()
     */
    public $before;

    /**
     * @var string|array|\DateTime The minimum Post Date that resulting entries can have.
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
     *     .after(now|date_modify('-7 days'))
     *     .all() %}
     * ```
     * @used-by after()
     */
    public $after;

    /**
     * @var mixed The Expiry Date that the resulting entries must have.
     * @used-by expiryDate()
     */
    public $expiryDate;

    /**
     * @inheritdoc
     */
    protected $defaultOrderBy = ['entries.postDate' => SORT_DESC];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($elementType, array $config = [])
    {
        // Default status
        if (!isset($config['status'])) {
            $config['status'] = ['live'];
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
     * Sets the [[$sectionId]] property based on a given section(s)’s handle(s).
     *
     * @param string|string[]|Section|null $value The property value
     * @return static self reference
     * @uses $sectionId
     */
    public function section($value)
    {
        if ($value instanceof Section) {
            $this->structureId = ($value->structureId ?: false);
            $this->sectionId = $value->id;
        } else if ($value !== null) {
            $this->sectionId = (new Query())
                ->select(['id'])
                ->from(['{{%sections}}'])
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->sectionId = null;
        }

        return $this;
    }

    /**
     * Sets the [[$sectionId]] property.
     *
     * @param int|int[]|null $value The property value
     * @return static self reference
     * @uses $sectionId
     */
    public function sectionId($value)
    {
        $this->sectionId = $value;
        return $this;
    }

    /**
     * Sets the [[$typeId]] property based on a given entry type(s)’s handle(s).
     *
     * @param string|string[]|EntryType|null $value The property value
     * @return static self reference
     * @uses $typeId
     */
    public function type($value)
    {
        if ($value instanceof EntryType) {
            $this->typeId = $value->id;
        } else if ($value !== null) {
            $this->typeId = (new Query())
                ->select(['id'])
                ->from(['{{%entrytypes}}'])
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->typeId = null;
        }

        return $this;
    }

    /**
     * Sets the [[$typeId]] property.
     *
     * @param int|int[]|null $value The property value
     * @return static self reference
     * @uses $typeId
     */
    public function typeId($value)
    {
        $this->typeId = $value;
        return $this;
    }

    /**
     * Sets the [[$authorId]] property.
     *
     * @param int|int[]|null $value The property value
     * @return static self reference
     * @uses $authorId
     */
    public function authorId($value)
    {
        $this->authorId = $value;
        return $this;
    }

    /**
     * Sets the [[$authorGroupId]] property based on a given user group(s)’s handle(s).
     *
     * @param string|string[]|null $value The property value
     * @return static self reference
     * @uses $authorGroupId
     */
    public function authorGroup($value)
    {
        if ($value instanceof UserGroup) {
            $this->authorGroupId = $value->id;
        } else if ($value !== null) {
            $this->authorGroupId = (new Query())
                ->select(['id'])
                ->from(['{{%usergroups}}'])
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->authorGroupId = null;
        }

        return $this;
    }

    /**
     * Sets the [[$authorGroupId]] property.
     *
     * @param int|int[]|null $value The property value
     * @return static self reference
     * @uses $authorGroupId
     */
    public function authorGroupId($value)
    {
        $this->authorGroupId = $value;
        return $this;
    }

    /**
     * Sets the [[$postDate]] property.
     *
     * @param mixed $value The property value
     * @return static self reference
     * @uses $postDate
     */
    public function postDate($value)
    {
        $this->postDate = $value;
        return $this;
    }

    /**
     * Sets the [[$before]] property.
     *
     * @param string|array|\DateTime $value The property value
     * @return static self reference
     * @uses $before
     */
    public function before($value)
    {
        $this->before = $value;
        return $this;
    }

    /**
     * Sets the [[$after]] property.
     *
     * @param string|array|\DateTime $value The property value
     * @return static self reference
     * @uses $after
     */
    public function after($value)
    {
        $this->after = $value;
        return $this;
    }

    /**
     * Sets the [[$expiryDate]] property.
     *
     * @param mixed $value The property value
     * @return static self reference
     * @uses $expiryDate
     */
    public function expiryDate($value)
    {
        $this->expiryDate = $value;
        return $this;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        // See if 'section', 'type', or 'authorGroup' were set to invalid handles
        if ($this->sectionId === [] || $this->typeId === [] || $this->authorGroupId === []) {
            return false;
        }

        $this->joinElementTable('entries');

        $this->query->select([
            'entries.sectionId',
            'entries.typeId',
            'entries.authorId',
            'entries.postDate',
            'entries.expiryDate',
        ]);

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
            $this->subQuery->andWhere(Db::parseParam('entries.typeId', $this->typeId));
        }

        if (Craft::$app->getEdition() === Craft::Pro) {
            if ($this->authorId) {
                $this->subQuery->andWhere(Db::parseParam('entries.authorId', $this->authorId));
            }

            if ($this->authorGroupId) {
                $this->subQuery
                    ->innerJoin('{{%usergroups_users}} usergroups_users', '[[usergroups_users.userId]] = [[entries.authorId]]')
                    ->andWhere(Db::parseParam('usergroups_users.groupId', $this->authorGroupId));
            }
        }

        $this->_applyEditableParam();
        $this->_applySectionIdParam();
        $this->_applyRefParam();

        return parent::beforePrepare();
    }

    /**
     * @inheritdoc
     */
    protected function statusCondition(string $status)
    {
        $currentTimeDb = Db::prepareDateForDb(new \DateTime());

        switch ($status) {
            case Entry::STATUS_LIVE:
                return [
                    'and',
                    [
                        'elements.enabled' => true,
                        'elements_sites.enabled' => true
                    ],
                    ['<=', 'entries.postDate', $currentTimeDb],
                    [
                        'or',
                        ['entries.expiryDate' => null],
                        ['>', 'entries.expiryDate', $currentTimeDb]
                    ]
                ];
            case Entry::STATUS_PENDING:
                return [
                    'and',
                    [
                        'elements.enabled' => true,
                        'elements_sites.enabled' => true,
                    ],
                    ['>', 'entries.postDate', $currentTimeDb]
                ];
            case Entry::STATUS_EXPIRED:
                return [
                    'and',
                    [
                        'elements.enabled' => true,
                        'elements_sites.enabled' => true
                    ],
                    ['not', ['entries.expiryDate' => null]],
                    ['<=', 'entries.expiryDate', $currentTimeDb]
                ];
            default:
                return parent::statusCondition($status);
        }
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
        if (!$this->editable) {
            return;
        }

        $user = Craft::$app->getUser()->getIdentity();

        if (!$user) {
            throw new QueryAbortedException();
        }

        // Limit the query to only the sections the user has permission to edit
        $this->subQuery->andWhere([
            'entries.sectionId' => Craft::$app->getSections()->getEditableSectionIds()
        ]);

        // Enforce the editPeerEntries permissions for non-Single sections
        foreach (Craft::$app->getSections()->getEditableSections() as $section) {
            if ($section->type != Section::TYPE_SINGLE && !$user->can('editPeerEntries:' . $section->id)) {
                $this->subQuery->andWhere([
                    'or',
                    ['not', ['entries.sectionId' => $section->id]],
                    ['entries.authorId' => $user->id]
                ]);
            }
        }
    }

    /**
     * Applies the 'sectionId' param to the query being prepared.
     */
    private function _applySectionIdParam()
    {
        if ($this->sectionId) {
            // Should we set the structureId param?
            if ($this->structureId === null && (!is_array($this->sectionId) || count($this->sectionId) === 1)) {
                $structureId = (new Query())
                    ->select(['structureId'])
                    ->from(['{{%sections}}'])
                    ->where(Db::parseParam('id', $this->sectionId))
                    ->scalar();
                $this->structureId = $structureId ? (int)$structureId : false;
            }

            $this->subQuery->andWhere(Db::parseParam('entries.sectionId', $this->sectionId));
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
                        Db::parseParam('elements_sites.slug', $parts[1])
                    ];
                    $joinSections = true;
                }
            }
        }

        $this->subQuery->andWhere($condition);

        if ($joinSections) {
            $this->subQuery->innerJoin('{{%sections}} sections', '[[sections.id]] = [[entries.sectionId]]');
        }
    }
}
