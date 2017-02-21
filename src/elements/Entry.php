<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\elements;

use Craft;
use craft\base\Element;
use craft\controllers\ElementIndexesController;
use craft\db\Query;
use craft\elements\actions\Delete;
use craft\elements\actions\Edit;
use craft\elements\actions\NewChild;
use craft\elements\actions\SetStatus;
use craft\elements\actions\View;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\EntryQuery;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
use craft\models\EntryType;
use craft\models\Section;
use craft\records\Entry as EntryRecord;
use craft\validators\DateTimeValidator;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Entry represents an entry element.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Entry extends Element
{
    // Constants
    // =========================================================================

    const STATUS_LIVE = 'live';
    const STATUS_PENDING = 'pending';
    const STATUS_EXPIRED = 'expired';

    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Entry');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle()
    {
        return 'entry';
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_LIVE => Craft::t('app', 'Live'),
            self::STATUS_PENDING => Craft::t('app', 'Pending'),
            self::STATUS_EXPIRED => Craft::t('app', 'Expired'),
            self::STATUS_DISABLED => Craft::t('app', 'Disabled')
        ];
    }

    /**
     * @inheritdoc
     *
     * @return EntryQuery The newly created [[EntryQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new EntryQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context = null): array
    {
        if ($context === 'index') {
            $sections = Craft::$app->getSections()->getEditableSections();
            $editable = true;
        } else {
            $sections = Craft::$app->getSections()->getAllSections();
            $editable = false;
        }

        $sectionIds = [];
        $singleSectionIds = [];
        $sectionsByType = [];

        foreach ($sections as $section) {
            $sectionIds[] = $section->id;

            if ($section->type == Section::TYPE_SINGLE) {
                $singleSectionIds[] = $section->id;
            } else {
                $sectionsByType[$section->type][] = $section;
            }
        }

        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('app', 'All entries'),
                'criteria' => [
                    'sectionId' => $sectionIds,
                    'editable' => $editable
                ],
                'defaultSort' => ['postDate', 'desc']
            ]
        ];

        if (!empty($singleSectionIds)) {
            $sources[] = [
                'key' => 'singles',
                'label' => Craft::t('app', 'Singles'),
                'criteria' => [
                    'sectionId' => $singleSectionIds,
                    'editable' => $editable
                ],
                'defaultSort' => ['title', 'asc']
            ];
        }

        $sectionTypes = [
            Section::TYPE_CHANNEL => Craft::t('app', 'Channels'),
            Section::TYPE_STRUCTURE => Craft::t('app', 'Structures')
        ];

        foreach ($sectionTypes as $type => $heading) {
            if (!empty($sectionsByType[$type])) {
                $sources[] = ['heading' => $heading];

                foreach ($sectionsByType[$type] as $section) {
                    $source = [
                        'key' => 'section:'.$section->id,
                        'label' => Craft::t('site', $section->name),
                        'data' => [
                            'type' => $type,
                            'handle' => $section->handle
                        ],
                        'criteria' => [
                            'sectionId' => $section->id,
                            'editable' => $editable
                        ]
                    ];

                    if ($type == Section::TYPE_STRUCTURE) {
                        $source['defaultSort'] = ['structure', 'asc'];
                        $source['structureId'] = $section->structureId;
                        $source['structureEditable'] = Craft::$app->getUser()->checkPermission('publishEntries:'.$section->id);
                    } else {
                        $source['defaultSort'] = ['postDate', 'desc'];
                    }

                    $sources[] = $source;
                }
            }
        }

        return $sources;
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source = null): array
    {
        // Get the section(s) we need to check permissions on
        switch ($source) {
            case '*':
                $sections = Craft::$app->getSections()->getEditableSections();
                break;
            case 'singles':
                $sections = Craft::$app->getSections()->getSectionsByType(Section::TYPE_SINGLE);
                break;
            default:
                if (
                    preg_match('/^section:(\d+)$/', $source, $matches) &&
                    ($section = Craft::$app->getSections()->getSectionById($matches[1])) !== null
                ) {
                    $sections = [$section];
                }
        }

        // Now figure out what we can do with these
        $actions = [];

        /** @var Section[] $sections */
        if (!empty($sections)) {
            $userSessionService = Craft::$app->getUser();
            $canSetStatus = true;
            $canEdit = false;

            foreach ($sections as $section) {
                $canPublishEntries = $userSessionService->checkPermission('publishEntries:'.$section->id);

                // Only show the Set Status action if we're sure they can make changes in all the sections
                if (!(
                    $canPublishEntries &&
                    ($section->type == Section::TYPE_SINGLE || $userSessionService->checkPermission('publishPeerEntries:'.$section->id))
                )
                ) {
                    $canSetStatus = false;
                }

                // Show the Edit action if they can publish changes to *any* of the sections
                // (the trigger will disable itself for entries that aren't editable)
                if ($canPublishEntries) {
                    $canEdit = true;
                }
            }

            // Set Status
            if ($canSetStatus) {
                $actions[] = SetStatus::class;
            }

            // Edit
            if ($canEdit) {
                $actions[] = Craft::$app->getElements()->createAction([
                    'type' => Edit::class,
                    'label' => Craft::t('app', 'Edit entry'),
                ]);
            }

            // View
            $showViewAction = ($source === '*' || $source === 'singles');

            if (!$showViewAction) {
                // They are viewing a specific section. See if it has URLs for the requested site
                $controller = Craft::$app->controller;
                if ($controller instanceof ElementIndexesController) {
                    $siteId = $controller->getElementQuery()->siteId ?: Craft::$app->getSites()->currentSite->id;
                    if (isset($sections[0]->siteSettings[$siteId]) && $sections[0]->siteSettings[$siteId]->hasUrls) {
                        $showViewAction = true;
                    }
                }
            }

            if ($showViewAction) {
                // View
                $actions[] = Craft::$app->getElements()->createAction([
                    'type' => View::class,
                    'label' => Craft::t('app', 'View entry'),
                ]);
            }

            // Channel/Structure-only actions
            if ($source !== '*' && $source !== 'singles') {
                $section = $sections[0];

                // New child?
                if (
                    $section->type == Section::TYPE_STRUCTURE &&
                    $userSessionService->checkPermission('createEntries:'.$section->id)
                ) {
                    $structure = Craft::$app->getStructures()->getStructureById($section->structureId);

                    if ($structure) {
                        $actions[] = Craft::$app->getElements()->createAction([
                            'type' => NewChild::class,
                            'label' => Craft::t('app', 'Create a new child entry'),
                            'maxLevels' => $structure->maxLevels,
                            'newChildUrl' => 'entries/'.$section->handle.'/new',
                        ]);
                    }
                }

                // Delete?
                if (
                    $userSessionService->checkPermission('deleteEntries:'.$section->id) &&
                    $userSessionService->checkPermission('deletePeerEntries:'.$section->id)
                ) {
                    $actions[] = Craft::$app->getElements()->createAction([
                        'type' => Delete::class,
                        'confirmationMessage' => Craft::t('app', 'Are you sure you want to delete the selected entries?'),
                        'successMessage' => Craft::t('app', 'Entries deleted.'),
                    ]);
                }
            }
        }

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'uri' => Craft::t('app', 'URI'),
            'postDate' => Craft::t('app', 'Post Date'),
            'expiryDate' => Craft::t('app', 'Expiry Date'),
            'elements.dateCreated' => Craft::t('app', 'Date Created'),
            'elements.dateUpdated' => Craft::t('app', 'Date Updated'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes = [
            'title' => ['label' => Craft::t('app', 'Title')],
            'section' => ['label' => Craft::t('app', 'Section')],
            'type' => ['label' => Craft::t('app', 'Entry Type')],
            'author' => ['label' => Craft::t('app', 'Author')],
            'slug' => ['label' => Craft::t('app', 'Slug')],
            'uri' => ['label' => Craft::t('app', 'URI')],
            'postDate' => ['label' => Craft::t('app', 'Post Date')],
            'expiryDate' => ['label' => Craft::t('app', 'Expiry Date')],
            'link' => ['label' => Craft::t('app', 'Link'), 'icon' => 'world'],
            'id' => ['label' => Craft::t('app', 'ID')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
        ];

        // Hide Author from Craft Personal/Client
        if (Craft::$app->getEdition() !== Craft::Pro) {
            unset($attributes['author']);
        }

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = [];

        if ($source === '*') {
            $attributes[] = 'section';
        }

        if ($source !== 'singles') {
            $attributes[] = 'postDate';
            $attributes[] = 'expiryDate';
        }

        $attributes[] = 'author';
        $attributes[] = 'link';

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle)
    {
        if ($handle === 'author') {
            // Get the source element IDs
            $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

            $map = (new Query())
                ->select(['id as source', 'authorId as target'])
                ->from(['{{%entries}}'])
                ->where(['and', ['id' => $sourceElementIds], ['not', ['authorId' => null]]])
                ->all();

            return [
                'elementType' => User::class,
                'map' => $map
            ];
        }

        return parent::eagerLoadingMap($sourceElements, $handle);
    }

    /**
     * @inheritdoc
     */
    protected static function prepElementQueryForTableAttribute(ElementQueryInterface $elementQuery, string $attribute)
    {
        /** @var ElementQuery $elementQuery */
        if ($attribute === 'author') {
            $with = $elementQuery->with ?: [];
            $with[] = 'author';
            $elementQuery->with = $with;
        } else {
            parent::prepElementQueryForTableAttribute($elementQuery, $attribute);
        }
    }

    // Properties
    // =========================================================================

    /**
     * @var int|null Section ID
     */
    public $sectionId;

    /**
     * @var int|null Type ID
     */
    public $typeId;

    /**
     * @var int|null Author ID
     */
    public $authorId;

    /**
     * @var \DateTime|null Post date
     */
    public $postDate;

    /**
     * @var \DateTime|null Expiry date
     */
    public $expiryDate;

    /**
     * @var int|null New parent ID
     */
    public $newParentId;

    /**
     * @var string|null Revision notes
     */
    public $revisionNotes;

    /**
     * @var User|null
     */
    private $_author;

    /**
     * @var bool|null
     * @see _hasNewParent()
     */
    private $_hasNewParent;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->authorId === null) {
            $this->authorId = Craft::$app->getUser()->getId();
        }
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $names = parent::datetimeAttributes();
        $names[] = 'postDate';
        $names[] = 'expiryDate';

        return $names;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        $labels = parent::attributeLabels();

        // Use the entry type's title label
        $labels['title'] = $this->getType()->titleLabel;

        return $labels;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();

        if (!$this->getType()->hasTitleField) {
            // Don't validate the title
            $key = array_search([['title'], 'required'], $rules, true);
            if ($key !== -1) {
                array_splice($rules, $key, 1);
            }
        }

        $rules[] = [['sectionId', 'typeId', 'authorId', 'newParentId'], 'number', 'integerOnly' => true];
        $rules[] = [['postDate', 'expiryDate'], DateTimeValidator::class];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout()
    {
        return $this->getType()->getFieldLayout();
    }

    /**
     * @inheritdoc
     */
    public function getSupportedSites(): array
    {
        $sites = [];

        foreach ($this->getSection()->getSiteSettings() as $siteSettings) {
            $sites[] = [
                'siteId' => $siteSettings->siteId,
                'enabledByDefault' => $siteSettings->enabledByDefault
            ];
        }

        return $sites;
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException if [[siteId]] is not set to a site ID that the entry's section is enabled for
     */
    public function getUriFormat()
    {
        $sectionSiteSettings = $this->getSection()->getSiteSettings();

        if (!isset($sectionSiteSettings[$this->siteId])) {
            throw new InvalidConfigException('Entry\'s section ('.$this->sectionId.') is not enabled for site '.$this->siteId);
        }

        return $sectionSiteSettings[$this->siteId]->uriFormat;
    }

    /**
     * @inheritdoc
     */
    protected function route()
    {
        // Make sure that the entry is actually live
        if ($this->getStatus() != Entry::STATUS_LIVE) {
            return null;
        }

        // Make sure the section is set to have URLs for this site
        $siteId = Craft::$app->getSites()->currentSite->id;
        $sectionSiteSettings = $this->getSection()->getSiteSettings();

        if (!isset($sectionSiteSettings[$siteId]) || !$sectionSiteSettings[$siteId]->hasUrls) {
            return null;
        }

        return [
            'templates/render', [
                'template' => $sectionSiteSettings[$siteId]->template,
                'variables' => [
                    'entry' => $this,
                ]
            ]
        ];
    }

    /**
     * Returns the reference string to this element.
     *
     * @return string|null
     */
    public function getRef()
    {
        return $this->getSection()->handle.'/'.$this->slug;
    }

    /**
     * Returns the entry's section.
     *
     * @return Section
     * @throws InvalidConfigException if [[sectionId]] is missing or invalid
     */
    public function getSection(): Section
    {
        if ($this->sectionId === null) {
            throw new InvalidConfigException('Entry is missing its section ID');
        }

        if (($section = Craft::$app->getSections()->getSectionById($this->sectionId)) === null) {
            throw new InvalidConfigException('Invalid section ID: '.$this->sectionId);
        }

        return $section;
    }

    /**
     * Returns the type of entry.
     *
     * @return EntryType
     * @throws InvalidConfigException if [[typeId]] is missing or invalid
     */
    public function getType(): EntryType
    {
        if ($this->typeId === null) {
            throw new InvalidConfigException('Entry is missing its type ID');
        }

        $sectionEntryTypes = ArrayHelper::index($this->getSection()->getEntryTypes(), 'id');

        if (!isset($sectionEntryTypes[$this->typeId])) {
            throw new InvalidConfigException('Invalid entry type ID: '.$this->typeId);
        }

        return $sectionEntryTypes[$this->typeId];
    }

    /**
     * Returns the entry's author.
     *
     * @return User|null
     * @throws InvalidConfigException if [[authorId]] is set but invalid
     */
    public function getAuthor()
    {
        if ($this->_author !== null) {
            return $this->_author;
        }

        if ($this->authorId === null) {
            return null;
        }

        if (($this->_author = Craft::$app->getUsers()->getUserById($this->authorId)) === null) {
            throw new InvalidConfigException('Invalid author ID: '.$this->authorId);
        }

        return $this->_author;
    }

    /**
     * Sets the entry's author.
     *
     * @param User|null $author
     */
    public function setAuthor(User $author = null)
    {
        $this->_author = $author;
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        $status = parent::getStatus();

        if ($status == self::STATUS_ENABLED && $this->postDate) {
            $currentTime = DateTimeHelper::currentTimeStamp();
            $postDate = $this->postDate->getTimestamp();
            $expiryDate = ($this->expiryDate ? $this->expiryDate->getTimestamp() : null);

            if ($postDate <= $currentTime && ($expiryDate === null || $expiryDate > $currentTime)) {
                return self::STATUS_LIVE;
            }

            if ($postDate > $currentTime) {
                return self::STATUS_PENDING;
            }

            return self::STATUS_EXPIRED;
        }

        return $status;
    }

    /**
     * @inheritdoc
     */
    public function getIsEditable(): bool
    {
        return (
            Craft::$app->getUser()->checkPermission('publishEntries:'.$this->sectionId) && (
                $this->authorId == Craft::$app->getUser()->getIdentity()->id ||
                Craft::$app->getUser()->checkPermission('publishPeerEntries:'.$this->sectionId) ||
                $this->getSection()->type == Section::TYPE_SINGLE
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl()
    {
        $section = $this->getSection();

        // The slug *might* not be set if this is a Draft and they've deleted it for whatever reason
        $url = UrlHelper::cpUrl('entries/'.$section->handle.'/'.$this->id.($this->slug ? '-'.$this->slug : ''));

        if (Craft::$app->getIsMultiSite() && $this->siteId != Craft::$app->getSites()->currentSite->id) {
            $url .= '/'.$this->getSite()->handle;
        }

        return $url;
    }

    /**
     * @inheritdoc
     */
    public function setEagerLoadedElements(string $handle, array $elements)
    {
        if ($handle === 'author') {
            $author = $elements[0] ?? null;
            $this->setAuthor($author);
        } else {
            parent::setEagerLoadedElements($handle, $elements);
        }
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'author':
                $author = $this->getAuthor();

                return $author ? Craft::$app->getView()->renderTemplate('_elements/element', ['element' => $author]) : '';

            case 'section':
                return Craft::t('site', $this->getSection()->name);

            case 'type':
                return Craft::t('site', $this->getType()->name);
        }

        $r = parent::tableAttributeHtml($attribute);

        return $r;
    }

    /**
     * @inheritdoc
     */
    public function getEditorHtml(): string
    {
        $html = '';
        $view = Craft::$app->getView();

        // Show the Entry Type field?
        if ($this->id === null) {
            $entryTypes = $this->getSection()->getEntryTypes();

            if (count($entryTypes) > 1) {
                $entryTypeOptions = [];

                foreach ($entryTypes as $entryType) {
                    $entryTypeOptions[] = [
                        'label' => Craft::t('site', $entryType->name),
                        'value' => $entryType->id
                    ];
                }

                $html .= Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'selectField', [
                    [
                        'label' => Craft::t('app', 'Entry Type'),
                        'id' => 'entryType',
                        'value' => $this->typeId,
                        'options' => $entryTypeOptions,
                    ]
                ]);

                $typeInputId = $view->namespaceInputId('entryType');
                $js = <<<EOD
$('#{$typeInputId}').on('change', function(ev) {
    var \$typeInput = $(this),
        editor = \$typeInput.closest('.hud').data('elementEditor');
    if (editor) {
        editor.setElementAttribute('typeId', \$typeInput.val());
        editor.loadHud();
    }
});
EOD;
                $view->registerJs($js);
            }
        }

        if ($this->getType()->hasTitleField) {
            $html .= $view->renderTemplate('entries/_titlefield', [
                'entry' => $this
            ]);
        }

        $html .= parent::getEditorHtml();

        return $html;
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function beforeSave(bool $isNew): bool
    {
        $section = $this->getSection();
        $entryType = $this->getType();

        // Has the entry been assigned to a new parent?
        if ($this->_hasNewParent()) {
            if ($this->newParentId !== null) {
                $parentEntry = Craft::$app->getEntries()->getEntryById($this->newParentId, $this->siteId);

                if (!$parentEntry) {
                    throw new Exception('Invalid entry ID: '.$this->newParentId);
                }
            } else {
                $parentEntry = null;
            }

            $this->setParent($parentEntry);
        }

        // Verify that the section supports this site
        $sectionSiteSettings = $section->getSiteSettings();

        if (!isset($sectionSiteSettings[$this->siteId])) {
            throw new Exception("The section '{$section->name}' is not enabled for the site '{$this->siteId}'");
        }

        if ($section->type == Section::TYPE_SINGLE) {
            // Single entries don't have
            $this->authorId = null;
            $this->expiryDate = null;
        }

        if ($this->enabled && !$this->postDate) {
            // Default the post date to the current date/time
            $this->postDate = DateTimeHelper::currentUTCDateTime();
        }

        if (!$entryType->hasTitleField) {
            $this->title = Craft::$app->getView()->renderObjectTemplate($entryType->titleFormat, $this);
        }

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function afterSave(bool $isNew)
    {
        $section = $this->getSection();

        // Get the entry record
        if (!$isNew) {
            $record = EntryRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid entry ID: '.$this->id);
            }
        } else {
            $record = new EntryRecord();
            $record->id = $this->id;
        }

        $record->sectionId = $this->sectionId;
        $record->typeId = $this->typeId;
        $record->authorId = $this->authorId;
        $record->postDate = $this->postDate;
        $record->expiryDate = $this->expiryDate;
        $record->save(false);

        if ($section->type == Section::TYPE_STRUCTURE) {
            // Has the parent changed?
            if ($this->_hasNewParent()) {
                if ($this->newParentId === null) {
                    Craft::$app->getStructures()->appendToRoot($section->structureId, $this);
                } else {
                    Craft::$app->getStructures()->append($section->structureId, $this, $this->getParent());
                }
            }

            // Update the entry's descendants, who may be using this entry's URI in their own URIs
            Craft::$app->getElements()->updateDescendantSlugsAndUris($this, true, true);
        }

        // Save a new version
        if ($section->enableVersioning) {
            Craft::$app->getEntryRevisions()->saveVersion($this);
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterMoveInStructure(int $structureId)
    {
        // Was the entry moved within its section's structure?
        $section = $this->getSection();

        if ($section->type == Section::TYPE_STRUCTURE && $section->structureId == $structureId) {
            Craft::$app->getElements()->updateElementSlugAndUri($this, true, true, true);
        }

        parent::afterMoveInStructure($structureId);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns whether the entry has been assigned a new parent entry.
     *
     * @return bool
     * @see beforeSave()
     * @see afterSave()
     */
    private function _hasNewParent(): bool
    {
        if ($this->_hasNewParent !== null) {
            return $this->_hasNewParent;
        }

        return $this->_hasNewParent = $this->_checkForNewParent();
    }

    /**
     * Checks if the entry has been assigned a new parent entry.
     *
     * @return bool
     * @see _hasNewParent()
     */
    private function _checkForNewParent(): bool
    {
        // Make sure this is a Structure section
        if ($this->getSection()->type != Section::TYPE_STRUCTURE) {
            return false;
        }

        // Is it a brand new entry?
        if ($this->id === null) {
            return true;
        }

        // Was a new parent ID actually submitted?
        if ($this->newParentId === null) {
            return false;
        }

        // Is it set to the top level now, but it hadn't been before?
        if ($this->newParentId === '' && $this->level != 1) {
            return true;
        }

        // Is it set to be under a parent now, but didn't have one before?
        if ($this->newParentId !== '' && $this->level == 1) {
            return true;
        }

        // Is the parentId set to a different entry ID than its previous parent?
        $oldParentQuery = Entry::find();
        $oldParentQuery->ancestorOf($this);
        $oldParentQuery->ancestorDist(1);
        $oldParentQuery->status(null);
        $oldParentQuery->siteId($this->siteId);
        $oldParentQuery->enabledForSite(false);
        $oldParentQuery->select('elements.id');
        $oldParentId = $oldParentQuery->scalar();

        return $this->newParentId != $oldParentId;
    }
}
