<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\elements;

use Craft;
use craft\app\base\Element;
use craft\app\base\ElementInterface;
use craft\app\controllers\ElementIndexesController;
use craft\app\db\Query;
use craft\app\elements\actions\Delete;
use craft\app\elements\actions\Edit;
use craft\app\elements\actions\NewChild;
use craft\app\elements\actions\SetStatus;
use craft\app\elements\actions\View;
use craft\app\elements\db\ElementQuery;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\elements\db\EntryQuery;
use craft\app\events\SetStatusEvent;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\Db;
use craft\app\helpers\Url;
use craft\app\models\EntryType;
use craft\app\models\Section;
use craft\app\validators\DateTimeValidator;
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
    public static function displayName()
    {
        return Craft::t('app', 'Entry');
    }

    /**
     * @inheritdoc
     */
    public static function hasContent()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function getStatuses()
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
    public static function find()
    {
        return new EntryQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public static function getSources($context = null)
    {
        if ($context == 'index') {
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
            '*' => [
                'label' => Craft::t('app', 'All entries'),
                'criteria' => [
                    'sectionId' => $sectionIds,
                    'editable' => $editable
                ],
                'defaultSort' => ['postDate', 'desc']
            ]
        ];

        if ($singleSectionIds) {
            $sources['singles'] = [
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
                    $key = 'section:'.$section->id;

                    $sources[$key] = [
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
                        $sources[$key]['defaultSort'] = ['structure', 'asc'];
                        $sources[$key]['structureId'] = $section->structureId;
                        $sources[$key]['structureEditable'] = Craft::$app->getUser()->checkPermission('publishEntries:'.$section->id);
                    } else {
                        $sources[$key]['defaultSort'] = ['postDate', 'desc'];
                    }
                }
            }
        }

        // Allow plugins to modify the sources
        Craft::$app->getPlugins()->call('modifyEntrySources',
            [&$sources, $context]);

        return $sources;
    }

    /**
     * @inheritdoc
     */
    public static function getAvailableActions($source = null)
    {
        // Get the section(s) we need to check permissions on
        switch ($source) {
            case '*': {
                $sections = Craft::$app->getSections()->getEditableSections();
                break;
            }
            case 'singles': {
                $sections = Craft::$app->getSections()->getSectionsByType(Section::TYPE_SINGLE);
                break;
            }
            default: {
                if (preg_match('/^section:(\d+)$/', $source, $matches)) {
                    $section = Craft::$app->getSections()->getSectionById($matches[1]);

                    if ($section) {
                        $sections = [$section];
                    }
                }
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
                /** @var SetStatus $setStatusAction */
                $setStatusAction = Craft::$app->getElements()->createAction(SetStatus::class);
                $setStatusAction->on(SetStatus::EVENT_AFTER_SET_STATUS,
                    function (SetStatusEvent $event) {
                        if ($event->status == self::STATUS_ENABLED) {
                            // Set a Post Date as well
                            Craft::$app->getDb()->createCommand()
                                ->update(
                                    '{{%entries}}',
                                    ['postDate' => Db::prepareDateForDb(new \DateTime())],
                                    [
                                        'and',
                                        ['in', 'id', $event->elementIds],
                                        'postDate is null'
                                    ])
                                ->execute();
                        }
                    });
                $actions[] = $setStatusAction;
            }

            // Edit
            if ($canEdit) {
                $actions[] = Craft::$app->getElements()->createAction([
                    'type' => Edit::class,
                    'label' => Craft::t('app', 'Edit entry'),
                ]);
            }

            // View
            $showViewAction = ($source == '*' || $source == 'singles');

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
            if ($source != '*' && $source != 'singles') {
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

        // Allow plugins to add additional actions
        $allPluginActions = Craft::$app->getPlugins()->call('addEntryActions',
            [$source], true);

        foreach ($allPluginActions as $pluginActions) {
            $actions = array_merge($actions, $pluginActions);
        }

        return $actions;
    }

    /**
     * @inheritdoc
     */
    public static function defineSortableAttributes()
    {
        $attributes = [
            'title' => Craft::t('app', 'Title'),
            'uri' => Craft::t('app', 'URI'),
            'postDate' => Craft::t('app', 'Post Date'),
            'expiryDate' => Craft::t('app', 'Expiry Date'),
            'elements.dateCreated' => Craft::t('app', 'Date Created'),
            'elements.dateUpdated' => Craft::t('app', 'Date Updated'),
        ];

        // Allow plugins to modify the attributes
        Craft::$app->getPlugins()->call('modifyEntrySortableAttributes',
            [&$attributes]);

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public static function defineAvailableTableAttributes()
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
        if (Craft::$app->getEdition() != Craft::Pro) {
            unset($attributes['author']);
        }

        // Allow plugins to modify the attributes
        $pluginAttributes = Craft::$app->getPlugins()->call('defineAdditionalEntryTableAttributes', [], true);

        foreach ($pluginAttributes as $thisPluginAttributes) {
            $attributes = array_merge($attributes, $thisPluginAttributes);
        }

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultTableAttributes($source = null)
    {
        $attributes = [];

        if ($source == '*') {
            $attributes[] = 'section';
        }

        if ($source != 'singles') {
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
    public static function getTableAttributeHtml(ElementInterface $element, $attribute)
    {
        /** @var Entry $element */
        // First give plugins a chance to set this
        $pluginAttributeHtml = Craft::$app->getPlugins()->callFirst('getEntryTableAttributeHtml',
            [$element, $attribute], true);

        if ($pluginAttributeHtml !== null) {
            return $pluginAttributeHtml;
        }

        switch ($attribute) {
            case 'author': {
                $author = $element->getAuthor();

                if ($author) {
                    return Craft::$app->getView()->renderTemplate('_elements/element', [
                        'element' => $author
                    ]);
                } else {
                    return '';
                }
            }

            case 'section': {
                return Craft::t('site', $element->getSection()->name);
            }

            case 'type': {
                return Craft::t('site', $element->getType()->name);
            }

            default: {
                return parent::getTableAttributeHtml($element, $attribute);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public static function getElementQueryStatusCondition(ElementQueryInterface $query, $status)
    {
        $currentTimeDb = Db::prepareDateForDb(new \DateTime());

        switch ($status) {
            case Entry::STATUS_LIVE: {
                return [
                    'and',
                    'elements.enabled = 1',
                    'elements_i18n.enabled = 1',
                    "entries.postDate <= '{$currentTimeDb}'",
                    [
                        'or',
                        'entries.expiryDate is null',
                        "entries.expiryDate > '{$currentTimeDb}'"
                    ]
                ];
            }

            case Entry::STATUS_PENDING: {
                return [
                    'and',
                    'elements.enabled = 1',
                    'elements_i18n.enabled = 1',
                    "entries.postDate > '{$currentTimeDb}'"
                ];
            }

            case Entry::STATUS_EXPIRED: {
                return [
                    'and',
                    'elements.enabled = 1',
                    'elements_i18n.enabled = 1',
                    'entries.expiryDate is not null',
                    "entries.expiryDate <= '{$currentTimeDb}'"
                ];
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public static function getEagerLoadingMap($sourceElements, $handle)
    {
        if ($handle == 'author') {
            // Get the source element IDs
            $sourceElementIds = [];

            foreach ($sourceElements as $sourceElement) {
                $sourceElementIds[] = $sourceElement->id;
            }

            $map = (new Query())
                ->select('id as source, authorId as target')
                ->from('{{%entries}}')
                ->where(['in', 'id', $sourceElementIds])
                ->all();

            return [
                'elementType' => User::class,
                'map' => $map
            ];
        }

        return parent::getEagerLoadingMap($sourceElements, $handle);
    }

    /**
     * @inheritdoc
     */
    protected static function prepElementQueryForTableAttribute(ElementQueryInterface $elementQuery, $attribute)
    {
        /** @var ElementQuery $elementQuery */
        if ($attribute == 'author') {
            $with = $elementQuery->with ?: [];
            $with[] = 'author';
            $elementQuery->with = $with;
        } else {
            parent::prepElementQueryForTableAttribute($elementQuery, $attribute);
        }
    }

    /**
     * @inheritdoc
     */
    public static function getEditorHtml(ElementInterface $element)
    {
        /** @var Entry $element */
        $html = '';
        $view = Craft::$app->getView();

        // Show the Entry Type field?
        if (!$element->id) {
            $entryTypes = $element->getSection()->getEntryTypes();

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
                        'value' => $element->typeId,
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

        if ($element->getType()->hasTitleField) {
            $html .= $view->renderTemplate('entries/_titlefield',
                [
                    'entry' => $element
                ]);
        }

        $html .= parent::getEditorHtml($element);

        return $html;
    }

    /**
     * @inheritdoc Element::saveElement()
     *
     * @return boolean
     */
    public static function saveElement(ElementInterface $element, $params)
    {
        /** @var Entry $element */
        // Make sure we have an author for this.
        if (!$element->authorId) {
            if (!empty($params['author'])) {
                $element->authorId = $params['author'];
            } else {
                $element->authorId = Craft::$app->getUser()->getId();
            }
        }

        // Route this through \craft\app\services\Entries::saveEntry() so the proper entry events get fired.
        return Craft::$app->getEntries()->saveEntry($element);
    }

    /**
     * Routes the request when the URI matches an element.
     *
     * @param ElementInterface $element
     *
     * @return array|bool|mixed
     */
    public static function getElementRoute(ElementInterface $element)
    {
        /** @var Entry $element */
        // Make sure that the entry is actually live
        if ($element->getStatus() == Entry::STATUS_LIVE) {
            // Make sure the section is set to have URLs for this site
            $siteId = Craft::$app->getSites()->currentSite->id;
            $sectionSiteSettings = $element->getSection()->getSiteSettings();

            if (isset($sectionSiteSettings[$siteId]) && $sectionSiteSettings[$siteId]->hasUrls) {
                return [
                    'templates/render',
                    [
                        'template' => $sectionSiteSettings[$siteId]->template,
                        'variables' => [
                            'entry' => $element
                        ]
                    ]
                ];
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public static function onAfterMoveElementInStructure(ElementInterface $element, $structureId)
    {
        /** @var Entry $element */
        // Was the entry moved within its section's structure?
        $section = $element->getSection();

        if ($section->type == Section::TYPE_STRUCTURE && $section->structureId == $structureId) {
            Craft::$app->getElements()->updateElementSlugAndUri($element, true, true, true);
        }
    }

    // Properties
    // =========================================================================

    /**
     * @var integer Section ID
     */
    public $sectionId;

    /**
     * @var integer Type ID
     */
    public $typeId;

    /**
     * @var integer Author ID
     */
    public $authorId;

    /**
     * @var \DateTime Post date
     */
    public $postDate;

    /**
     * @var \DateTime Expiry date
     */
    public $expiryDate;

    /**
     * @var integer New parent ID
     */
    public $newParentId;

    /**
     * @var string Revision notes
     */
    public $revisionNotes;

    /**
     * @var User
     */
    private $_author;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function datetimeAttributes()
    {
        $names = parent::datetimeAttributes();
        $names[] = 'postDate';
        $names[] = 'expiryDate';

        return $names;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
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
    public function getSupportedSites()
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
    public function getSection()
    {
        if (!$this->sectionId) {
            throw new InvalidConfigException('Entry is missing its section ID');
        }

        $section = Craft::$app->getSections()->getSectionById($this->sectionId);

        if (!$section) {
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
    public function getType()
    {
        if (!$this->typeId) {
            throw new InvalidConfigException('Entry is missing its type ID');
        }

        $sectionEntryTypes = $this->getSection()->getEntryTypes('id');

        if (!isset($sectionEntryTypes[$this->typeId])) {
            throw new InvalidConfigException('Invalid entry type ID: '.$this->typeId);
        }

        return $sectionEntryTypes[$this->typeId];
    }

    /**
     * Returns the entry's author.
     *
     * @return User|null
     */
    public function getAuthor()
    {
        if (!isset($this->_author) && $this->authorId) {
            $this->_author = Craft::$app->getUsers()->getUserById($this->authorId);
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

            if ($postDate <= $currentTime && (!$expiryDate || $expiryDate > $currentTime)) {
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
    public function getIsEditable()
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
        $url = Url::getCpUrl('entries/'.$section->handle.'/'.$this->id.($this->slug ? '-'.$this->slug : ''));

        if (Craft::$app->getIsMultiSite() && $this->siteId != Craft::$app->getSites()->currentSite->id) {
            $url .= '/'.$this->getSite()->handle;
        }

        return $url;
    }

    /**
     * @inheritdoc
     */
    public function setEagerLoadedElements($handle, $elements)
    {
        if ($handle == 'author') {
            $author = isset($elements[0]) ? $elements[0] : null;
            $this->setAuthor($author);
        } else {
            parent::setEagerLoadedElements($handle, $elements);
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function resolveStructureId()
    {
        return $this->getSection()->structureId;
    }
}
