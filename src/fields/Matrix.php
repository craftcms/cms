<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementContainerFieldInterface;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\base\GqlInlineFragmentFieldInterface;
use craft\base\GqlInlineFragmentInterface;
use craft\base\MergeableFieldInterface;
use craft\base\NestedElementInterface;
use craft\behaviors\EventBehavior;
use craft\db\Query;
use craft\db\Table as DbTable;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\EntryQuery;
use craft\elements\ElementCollection;
use craft\elements\Entry;
use craft\elements\NestedElementManager;
use craft\elements\User;
use craft\enums\ElementIndexViewMode;
use craft\enums\PropagationMethod;
use craft\errors\InvalidFieldException;
use craft\events\BulkElementsEvent;
use craft\events\CancelableEvent;
use craft\events\DefineEntryTypesForFieldEvent;
use craft\fields\conditions\EmptyFieldConditionRule;
use craft\gql\arguments\elements\Entry as EntryArguments;
use craft\gql\resolvers\elements\Entry as EntryResolver;
use craft\gql\types\generators\EntryType as EntryTypeGenerator;
use craft\gql\types\input\Matrix as MatrixInputType;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\Gql;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Queue;
use craft\helpers\StringHelper;
use craft\i18n\Translation;
use craft\models\EntryType;
use craft\queue\jobs\ApplyNewPropagationMethod;
use craft\queue\jobs\ResaveElements;
use craft\validators\ArrayValidator;
use craft\validators\StringValidator;
use craft\validators\UriFormatValidator;
use craft\web\assets\matrix\MatrixAsset;
use craft\web\View;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\db\Expression;

/**
 * Matrix field type
 *
 * @phpstan-import-type EagerLoadingMap from ElementInterface
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Matrix extends Field implements
    ElementContainerFieldInterface,
    EagerLoadingFieldInterface,
    MergeableFieldInterface,
    GqlInlineFragmentFieldInterface
{
    /**
     * @event DefineEntryTypesForFieldEvent The event that is triggered when defining the available entry types.
     * @since 5.0.0
     */
    public const EVENT_DEFINE_ENTRY_TYPES = 'defineEntryTypes';

    /** @since 5.0.0 */
    public const VIEW_MODE_CARDS = 'cards';
    /** @since 5.0.0 */
    public const VIEW_MODE_BLOCKS = 'blocks';
    /** @since 5.0.0 */
    public const VIEW_MODE_INDEX = 'index';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Matrix');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): string
    {
        return 'binary';
    }

    /**
     * @inheritdoc
     */
    public static function supportedTranslationMethods(): array
    {
        // Don't ever automatically propagate values to other sites.
        return [
            self::TRANSLATION_METHOD_SITE,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function phpType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', EntryQuery::class, ElementCollection::class, Entry::class);
    }

    /**
     * @inheritdoc
     */
    public static function dbType(): array|string|null
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public static function queryCondition(array $instances, mixed $value, array &$params): array
    {
        /** @var self $field */
        $field = reset($instances);
        $ns = $field->handle . '_' . StringHelper::randomString(5);

        $existsQuery = (new Query())
            ->from(["entries_$ns" => DbTable::ENTRIES])
            ->innerJoin(["elements_$ns" => DbTable::ELEMENTS], "[[elements_$ns.id]] = [[entries_$ns.id]]")
            ->innerJoin(["elements_owners_$ns" => DbTable::ELEMENTS_OWNERS], "[[elements_owners_$ns.elementId]] = [[elements_$ns.id]]")
            ->andWhere([
                "entries_$ns.fieldId" => $field->id,
                "elements_$ns.enabled" => true,
                "elements_$ns.dateDeleted" => null,
                "[[elements_owners_$ns.ownerId]]" => new Expression('[[elements.id]]'),
            ]);

        if ($value === 'not :empty:') {
            $value = ':notempty:';
        }

        if ($value === ':empty:') {
            return ['not exists', $existsQuery];
        }

        if ($value !== ':notempty:') {
            $ids = $value;
            if (!is_array($ids)) {
                $ids = is_string($ids) ? StringHelper::split($ids) : [$ids];
            }

            $ids = array_map(fn($id) => $id instanceof Entry ? $id->id : (int)$id, $ids);

            $existsQuery->andWhere(["entries_$ns.id" => $ids]);
        }

        return ['exists', $existsQuery];
    }

    /**
     * Returns the “Default Table Columns” options for the given entry types.
     *
     * @param EntryType[] $entryTypes
     * @return array
     * @since 5.0.0
     */
    public static function defaultTableColumnOptions(array $entryTypes): array
    {
        $fieldLayouts = array_map(fn(EntryType $entryType) => $entryType->getFieldLayout(), $entryTypes);
        $elementSources = Craft::$app->getElementSources();
        $tableColumns = array_merge(
            $elementSources->getAvailableTableAttributes(Entry::class),
            $elementSources->getTableAttributesForFieldLayouts($fieldLayouts),
        );

        $options = [];
        foreach ($tableColumns as $attribute => $column) {
            $options[] = ['label' => $column['label'], 'value' => $attribute];
        }
        return $options;
    }

    /**
     * @var int|null Min entries
     * @since 5.0.0
     */
    public ?int $minEntries = null;

    /**
     * @var int|null Max entries
     * @since 5.0.0
     */
    public ?int $maxEntries = null;

    /**
     * @var bool Enable versioning
     * @since 5.7.0
     */
    public bool $enableVersioning = false;

    /**
     * @var string The view mode
     * @phpstan-var self::VIEW_MODE_*
     * @since 5.0.0
     */
    public string $viewMode = self::VIEW_MODE_CARDS;

    /**
     * @var bool Whether cards should be shown in a multi-column grid
     * @since 5.0.0
     */
    public bool $showCardsInGrid = false;

    /**
     * @var bool Include table view in element indexes
     * @since 5.0.0
     */
    public bool $includeTableView = false;

    /**
     * @var string[] The default table columns to show in table view
     * @since 5.0.0
     */
    public array $defaultTableColumns = [];

    /**
     * @var string The default view mode that should be used
     * if the field's view mode is set to element index and has "Include Table View" turned on.
     * @since 5.5.0
     */
    public string $defaultIndexViewMode = 'cards';

    /**
     * @var int|null The total entries to display per page within element indexes
     * @since 5.0.0
     */
    public ?int $pageSize = null;

    /**
     * @var string|null The “New entry” button label.
     * @since 5.0.0
     */
    public ?string $createButtonLabel = null;

    /**
     * @var PropagationMethod Propagation method
     *
     * This will be set to one of the following:
     *
     * - [[PropagationMethod::None]] – Only save entries in the site they were created in
     * - [[PropagationMethod::SiteGroup]] – Save entries to other sites in the same site group
     * - [[PropagationMethod::Language]] – Save entries to other sites with the same language
     * - [[PropagationMethod::Custom]] – Save entries to other sites based on a custom [[$propagationKeyFormat|propagation key format]]
     * - [[PropagationMethod::All]] – Save entries to all sites supported by the owner element
     *
     * @since 3.2.0
     */
    public PropagationMethod $propagationMethod = PropagationMethod::All;

    /**
     * @var string|null The field’s propagation key format, if [[propagationMethod]] is `custom`
     * @since 3.7.0
     */
    public ?string $propagationKeyFormat = null;

    /**
     * @var array{uriFormat:string|null,template?:string|null,errors?:array}[] Site settings
     * @since 5.0.0
     */
    public array $siteSettings = [];

    /**
     * @var EntryType[] The field’s available entry types
     * @see getEntryTypes()
     * @see setEntryTypes()
     */
    private array $_entryTypes = [];

    /**
     * @see entryManager()
     */
    private NestedElementManager $_entryManager;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // Config normalization
        unset($config['contentTable']);

        if (array_key_exists('localizeBlocks', $config)) {
            $config['propagationMethod'] = $config['localizeBlocks'] ? 'none' : 'all';
            unset($config['localizeBlocks']);
        }

        if (isset($config['entryTypes']) && $config['entryTypes'] === '') {
            $config['entryTypes'] = [];
        }

        if (array_key_exists('minBlocks', $config)) {
            $config['minEntries'] = ArrayHelper::remove($config, 'minBlocks');
        }
        if (array_key_exists('maxBlocks', $config)) {
            $config['maxEntries'] = ArrayHelper::remove($config, 'maxBlocks');
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        foreach ($this->siteSettings as &$siteSettings) {
            if (($siteSettings['uriFormat'] ?? null) === '') {
                unset($siteSettings['uriFormat']);
            }
            if (($siteSettings['template'] ?? null) === '') {
                unset($siteSettings['template']);
            }
        }

        if ($this->viewMode === self::VIEW_MODE_BLOCKS) {
            $this->includeTableView = false;
            $this->pageSize = null;
        }

        if (!$this->includeTableView) {
            $this->defaultTableColumns = [];
        }

        if ($this->minEntries === 0) {
            $this->minEntries = null;
        }
        if ($this->maxEntries === 0) {
            $this->maxEntries = null;
        }
    }

    /**
     * @inheritdoc
     */
    public function settingsAttributes(): array
    {
        return ArrayHelper::withoutValue(parent::settingsAttributes(), 'localizeEntries');
    }

    /**
     * @inheritdoc
     */
    public function getSettings(): array
    {
        $settings = parent::getSettings();
        $settings['entryTypes'] = array_map(
            fn(EntryType $entryType) => $entryType->getUsageConfig(),
            $this->getEntryTypes(),
        );
        return $settings;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['entryTypes'], ArrayValidator::class, 'min' => 1, 'skipOnEmpty' => false];
        $rules[] = [['siteSettings'], fn() => $this->validateSiteSettings()];
        $rules[] = [['minEntries', 'maxEntries'], 'integer', 'min' => 0];
        $rules[] = [['viewMode'], 'in', 'range' => [
            self::VIEW_MODE_CARDS,
            self::VIEW_MODE_INDEX,
            self::VIEW_MODE_BLOCKS,
        ]];
        return $rules;
    }

    private function validateSiteSettings(): void
    {
        foreach ($this->siteSettings as $uid => &$siteSettings) {
            unset($siteSettings['errors']);

            if (isset($siteSettings['uriFormat'])) {
                // Remove any leading or trailing slashes/spaces
                $siteSettings['uriFormat'] = trim($siteSettings['uriFormat'], '/ ');

                if (!(new UriFormatValidator())->validate($siteSettings['uriFormat'], $error)) {
                    $error = str_replace(Craft::t('yii', 'the input value'), Craft::t('app', 'Entry URI Format'), $error);
                    $siteSettings['errors']['uriFormat'][] = $error;
                    $this->addError("siteSettings[$uid].uriFormat", $error);
                }
            }

            if (isset($siteSettings['template'])) {
                if (!(new StringValidator(['max' => 500]))->validate($siteSettings['template'], $error)) {
                    $error = str_replace(Craft::t('yii', 'the input value'), Craft::t('app', 'Template'), $error);
                    $siteSettings['errors']['template'][] = $error;
                    $this->addError("siteSettings[$uid].template", $error);
                }
            }
        }
    }

    private function entryManager(): NestedElementManager
    {
        if (!isset($this->_entryManager)) {
            $this->_entryManager = new NestedElementManager(
                Entry::class,
                fn(ElementInterface $owner) => $this->createEntryQuery($owner),
                [
                    'field' => $this,
                    'criteria' => [
                        'fieldId' => $this->id,
                    ],
                    'propagationMethod' => $this->propagationMethod,
                    'propagationKeyFormat' => $this->propagationKeyFormat,
                ],
            );

            $this->_entryManager->on(NestedElementManager::EVENT_AFTER_SAVE_ELEMENTS, [$this, 'afterSaveEntries']);
        }

        return $this->_entryManager;
    }

    /**
     * Returns the available entry types.
     *
     * @return EntryType[]
     */
    public function getEntryTypes(): array
    {
        return $this->_entryTypes;
    }

    /**
     * Returns the available entry types for the given owner element.
     *
     * @param Entry[] $value
     * @param ElementInterface|null $element
     * @return EntryType[]
     * @since 5.0.0
     */
    public function getEntryTypesForField(array $value, ?ElementInterface $element): array
    {
        $entryTypes = $this->getEntryTypes();

        // Fire a 'defineEntryTypes' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_ENTRY_TYPES)) {
            $event = new DefineEntryTypesForFieldEvent([
                'entryTypes' => $entryTypes,
                'element' => $element,
                'value' => $value,
            ]);
            $this->trigger(self::EVENT_DEFINE_ENTRY_TYPES, $event);
            $entryTypes = $event->entryTypes;
        }

        if (empty($entryTypes)) {
            throw new InvalidConfigException('At least one entry type is required.');
        }

        return array_values($entryTypes);
    }

    /**
     * Sets the available entry types.
     *
     * @param array<EntryType|int|string|array{id?:int,uid?:string,name?:string,handle?:string}> $entryTypes The entry types
     */
    public function setEntryTypes(array $entryTypes): void
    {
        $entriesService = Craft::$app->getEntries();
        $this->_entryTypes = array_values(array_filter(array_map(
            fn($entryType) => $entriesService->getEntryType($entryType),
            $entryTypes,
        )));
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayoutProviders(): array
    {
        return $this->getEntryTypes();
    }

    /**
     * @inheritdoc
     */
    public function getUriFormatForElement(NestedElementInterface $element): ?string
    {
        $site = $element->getSite();
        return $this->siteSettings[$site->uid]['uriFormat'] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getRouteForElement(NestedElementInterface $element): mixed
    {
        $site = $element->getSite();

        return [
            'templates/render', [
                'template' => $this->siteSettings[$site->uid]['template'] ?? '',
                'variables' => [
                    'entry' => $element,
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getSupportedSitesForElement(NestedElementInterface $element): array
    {
        try {
            $owner = $element->getOwner();
        } catch (InvalidConfigException) {
            $owner = $element->duplicateOf;
        }

        if (!$owner) {
            return [Craft::$app->getSites()->getPrimarySite()->id];
        }

        return $this->entryManager()->getSupportedSiteIds($owner);
    }

    /**
     * @inheritdoc
     */
    public function canViewElement(NestedElementInterface $element, User $user): ?bool
    {
        $owner = $element->getOwner();
        return $owner && Craft::$app->getElements()->canView($owner, $user);
    }

    /**
     * @inheritdoc
     */
    public function canSaveElement(NestedElementInterface $element, User $user): ?bool
    {
        $owner = $element->getOwner();

        if (!$owner) {
            return false;
        }

        if (Craft::$app->getElements()->canSave($owner, $user)) {
            return true;
        }

        // Check all the owners. Maybe the user can save one of the other ones?
        /** @phpstan-ignore-next-line  */
        if (!Craft::$app->getElements()->canSave($owner, $user) && !$owner->getIsRevision()) {
            foreach ($element->getOwners(['revisions' => false]) as $o) {
                if ($o->id !== $owner->id && Craft::$app->getElements()->canSave($o, $user)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function canDuplicateElement(NestedElementInterface $element, User $user): ?bool
    {
        $owner = $element->getOwner();

        if (!$owner || !Craft::$app->getElements()->canSave($owner, $user)) {
            return false;
        }

        // Make sure we aren't hitting the Max Entries limit
        return !$this->maxEntriesReached($owner);
    }

    /**
     * @inheritdoc
     */
    public function canDeleteElement(NestedElementInterface $element, User $user): ?bool
    {
        $owner = $element->getOwner();

        if (!$owner || !Craft::$app->getElements()->canSave($element->getOwner(), $user)) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function canDeleteElementForSite(NestedElementInterface $element, User $user): ?bool
    {
        return false;
    }

    private function maxEntriesReached(ElementInterface $owner): bool
    {
        return (
            $this->maxEntries &&
            $this->maxEntries <= $this->totalEntries($owner)
        );
    }

    private function totalEntries(ElementInterface $owner): int
    {
        /** @var EntryQuery|ElementCollection $value */
        $value = $owner->getFieldValue($this->handle);

        if ($value instanceof EntryQuery) {
            return (clone $value)
                ->status(null)
                ->siteId($owner->siteId)
                ->limit(null)
                ->count();
        }

        return $value->count();
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return $this->settingsHtml(false);
    }

    /**
     * @inheritdoc
     */
    public function getReadOnlySettingsHtml(): ?string
    {
        return $this->settingsHtml(true);
    }

    private function settingsHtml(bool $readOnly): string
    {
        $view = Craft::$app->getView();

        $entryTypes = Collection::make($this->getEntryTypes());
        $entryTypeSelectConfig = [
            'name' => 'entryTypes[]',
            'renderDefaultInput' => false,
            'allowOverrides' => true,
            'create' => true,
            'jsClass' => 'Craft.GroupedEntryTypeSelectInput',
            'errors' => $this->getErrors('entryTypes'),
            'data' => [
                'error-key' => 'entryTypes',
                'disabled' => $readOnly,
            ],
        ];

        if (!$readOnly) {
            $view->startJsBuffer();
            $namespace = $view->getNamespace();
            $view->setNamespace(null);
            $entryTypeSelectHtml = $view->namespaceInputs(fn() => Cp::entryTypeSelectHtml([
                ...$entryTypeSelectConfig,
                'id' => 'TEMP_ID',
            ]), $namespace);
            $view->setNamespace($namespace);
            $entryTypeSelectJs = $view->clearJsBuffer();
        }

        return $view->renderTemplate('_components/fieldtypes/Matrix/settings.twig', [
            'field' => $this,
            'entryTypes' => $entryTypes,
            'entryTypeSelectConfig' => $entryTypeSelectConfig,
            'entryTypeSelectHtml' => $entryTypeSelectHtml ?? null,
            'entryTypeSelectJs' => $entryTypeSelectJs ?? null,
            'defaultTableColumnOptions' => static::defaultTableColumnOptions($this->getEntryTypes()),
            'defaultCreateButtonLabel' => $this->defaultCreateButtonLabel(),
            'indexViewModes' => array_filter(
                Entry::indexViewModes(),
                fn(array $viewMode) => !($viewMode['structuresOnly'] ?? false),
            ),
            'readOnly' => $readOnly,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->_normalizeValueInternal($value, $element, false);
    }

    /**
     * @inheritdoc
     */
    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->_normalizeValueInternal($value, $element, true);
    }

    private function _normalizeValueInternal(mixed $value, ?ElementInterface $element, bool $fromRequest): mixed
    {
        if ($value instanceof ElementQueryInterface) {
            return $value;
        }

        $query = $this->createEntryQuery($element);

        // Set the initially matched elements if $value is already set, which is the case if there was a validation
        // error or we're loading an entry revision.
        if ($value === '') {
            $query->setCachedResult([]);
        } elseif ($value === '*') {
            // preload the nested entries so NestedElementManager::saveNestedElements() doesn't resave them all
            $query->drafts(null)->savedDraftsOnly()->status(null)->limit(null);
            $query->setCachedResult($query->all());
        } elseif ($element && is_array($value)) {
            $query->setCachedResult($this->_createEntriesFromSerializedData($value, $element, $fromRequest));
        } elseif (Craft::$app->getRequest()->getIsPreview()) {
            $query->withProvisionalDrafts();
        }

        return $query;
    }

    private function createEntryQuery(?ElementInterface $owner): EntryQuery
    {
        $query = Entry::find();

        // Existing element?
        if ($owner && $owner->id) {
            $query->attachBehavior(self::class, new EventBehavior([
                ElementQuery::EVENT_BEFORE_PREPARE => function(
                    CancelableEvent $event,
                    EntryQuery $query,
                ) use ($owner) {
                    $query->ownerId = $owner->id;

                    // Clear out id=false if this query was populated previously
                    if ($query->id === false) {
                        $query->id = null;
                    }

                    // If the owner is a revision, allow revision entries to be returned as well
                    if ($owner->getIsRevision()) {
                        $query
                            ->revisions(null)
                            ->trashed(null);
                    }
                },
            ], true));

            // Prepare the query for lazy eager loading
            $query->prepForEagerLoading($this->handle, $owner);
        } else {
            $query->id = false;
        }

        $query
            ->fieldId($this->id)
            ->siteId($owner->siteId ?? null);

        return $query;
    }

    /**
     * @inheritdoc
     */
    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        /** @var EntryQuery|ElementCollection $value */
        $serialized = [];
        $new = 0;

        foreach ($value->all() as $entry) {
            /** @var Entry $entry */
            $entryId = $entry->id ?? sprintf('new%s', ++$new);
            $serialized[$entryId] = [
                'title' => $entry->title,
                'slug' => $entry->slug,
                'type' => $entry->getType()->handle,
                'enabled' => $entry->enabled,
                'collapsed' => $entry->collapsed,
                'fields' => $entry->getSerializedFieldValues(),
            ];
        }

        return $serialized;
    }

    /**
     * @inheritdoc
     */
    public function serializeValueForDb(mixed $value, ElementInterface $element): mixed
    {
        /** @var EntryQuery|ElementCollection $value */
        $serialized = [];
        $new = 0;

        foreach ($value->all() as $entry) {
            /** @var Entry $entry */
            $entryId = $entry->id ?? sprintf('new%s', ++$new);
            $serialized[$entryId] = [
                'title' => $entry->title,
                'slug' => $entry->slug,
                'type' => $entry->getType()->handle,
                'enabled' => $entry->enabled,
                'collapsed' => $entry->collapsed,
                'fields' => $entry->getSerializedFieldValuesForDb(),
            ];
        }

        return $serialized;
    }

    /**
     * @inheritdoc
     */
    public function copyValue(ElementInterface $from, ElementInterface $to): void
    {
        // We'll do it later from afterElementPropagate()
    }

    /**
     * @inheritdoc
     */
    public function getElementConditionRuleType(): array|string|null
    {
        return EmptyFieldConditionRule::class;
    }

    /**
     * @inheritdoc
     */
    public function getIsTranslatable(?ElementInterface $element): bool
    {
        return $this->entryManager()->getIsTranslatable($element);
    }

    /**
     * @inheritdoc
     */
    protected function actionMenuItems(): array
    {
        $items = [];
        $view = Craft::$app->getView();

        if ($this->viewMode === self::VIEW_MODE_BLOCKS) {
            // Expand/Collapse all
            $expandAllId = sprintf('expand-all-%s', mt_rand());
            $collapseAllId = sprintf('collapse-all-%s', mt_rand());
            $items[] = [
                'id' => $expandAllId,
                'icon' => 'expand',
                'label' => StringHelper::upperCaseFirst(Craft::t('app', 'Expand all blocks', [
                    'type' => Entry::pluralLowerDisplayName(),
                ])),
            ];
            $items[] = [
                'id' => $collapseAllId,
                'icon' => 'collapse',
                'label' => StringHelper::upperCaseFirst(Craft::t('app', 'Collapse all blocks', [
                    'type' => Entry::pluralLowerDisplayName(),
                ])),
            ];
            $view->registerJsWithVars(fn($expandAllId, $collapseAllId, $fieldId) => <<<JS
(() => {
  const expandAllBtn = $('#' + $expandAllId);
  const collapseAllBtn = $('#' + $collapseAllId);
  const getBlocks = () => $('#' + $fieldId + ' > .blocks > .matrixblock');

  expandAllBtn.on('activate', () => {
    getBlocks().each((i, block) => {
      $(block).data('entry').expand();
    });
  });

  collapseAllBtn.on('activate', () => {
    getBlocks().each((i, block) => {
      $(block).data('entry').collapse();
    });
  });

  setTimeout(() => {
    const menu = expandAllBtn.closest('.menu').data('disclosureMenu');
    menu.on('show', () => {
      const blocks = getBlocks();
      menu.toggleItem(expandAllBtn[0], blocks.is('.collapsed'));
      menu.toggleItem(collapseAllBtn[0], blocks.is(':not(.collapsed)'));
    });
  }, 1);
})();
JS, [
                $view->namespaceInputId($expandAllId),
                $view->namespaceInputId($collapseAllId),
                $view->namespaceInputId($this->getInputId()),
            ]);
        }

        // Copy all
        if ($this->maxEntries !== 1 && $this->viewMode !== self::VIEW_MODE_INDEX) {
            if (!empty($items)) {
                $items[] = ['type' => 'hr'];
            }
            $copyAllId = sprintf('action-copy-all-%s', mt_rand());
            $items[] = [
                'id' => $copyAllId,
                'icon' => 'clone-dashed',
                'color' => \craft\enums\Color::Fuchsia,
                'label' => StringHelper::upperCaseFirst(Craft::t('app', 'Copy all {type}', [
                    'type' => Entry::pluralLowerDisplayName(),
                ])),
            ];

            if ($this->viewMode === self::VIEW_MODE_CARDS) {
                $copyAllJs = <<<JS
copyAllBtn.on('activate', () => {
  Craft.cp.copyElements(field.find('> .nested-element-cards > .elements > li > .element'));
});
JS;
            } else {
                $baseInfo = Json::encode([
                    'type' => Entry::class,
                    'fieldId' => $this->id,
                ]);
                $copyAllJs = <<<JS
copyAllBtn.on('activate', () => {
  const elementInfo = [];
  field.find('> .blocks > .matrixblock').each((i, element) => {
    element = $(element);
    elementInfo.push(Object.assign({
        id: element.data('id'),
        draftId: element.data('draftId'),
        revisionId: element.data('revisionId'),
        ownerId: element.data('ownerId'),
        siteId: element.data('siteId'),
      }, $baseInfo));
  });
  Craft.cp.copyElements(elementInfo);
});
JS;
            }

            $view->registerJsWithVars(fn($copyAllId, $fieldId) => <<<JS
(() => {
  const copyAllBtn = $('#' + $copyAllId);
  const field = $('#' + $fieldId);
  if (field.length) {
    $copyAllJs
  } else {
    setTimeout(() => {
      const menu = copyAllBtn.closest('.menu').data('disclosureMenu');
      menu.removeItem(copyAllBtn[0]);
    }, 1);
  }
})();
JS, [
                $view->namespaceInputId($copyAllId),
                $view->namespaceInputId($this->getInputId()),
            ]);
        }

        $parentItems = parent::actionMenuItems();

        if (!empty($items) && !empty($parentItems)) {
            return [
                ...$items,
                ['type' => 'hr'],
                ...$parentItems,
            ];
        }

        return [...$items, ...$parentItems];
    }

    /**
     * @inheritdoc
     */
    public function getTranslationDescription(?ElementInterface $element): ?string
    {
        return $this->entryManager()->getTranslationDescription($element);
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return match ($this->viewMode) {
            self::VIEW_MODE_BLOCKS => $this->blockInputHtml($value, $element),
            default => Html::tag('div', $this->nestedElementManagerHtml($element), [
                'id' => $this->getInputId(),
            ]),
        };
    }

    private function blockInputHtml(EntryQuery|ElementCollection|null $value, ?ElementInterface $element): string
    {
        if (!$element?->id) {
            $message = Craft::t('app', '{nestedType} can only be created after the {ownerType} has been saved.', [
                'nestedType' => Entry::pluralDisplayName(),
                'ownerType' => $element ? $element::lowerDisplayName() : Craft::t('app', 'element'),
            ]);
            return Html::tag('div', $message, ['class' => 'pane no-border zilch small']);
        }

        if ($element->hasEagerLoadedElements($this->handle)) {
            $value = $element->getEagerLoadedElements($this->handle)->all();
        }

        if ($value instanceof EntryQuery) {
            $value = $value->getCachedResult() ?? (clone $value)
                ->drafts(null)
                ->canonicalsOnly()
                ->status(null)
                ->limit(null)
                ->all();
        }

        $view = Craft::$app->getView();
        $id = $this->getInputId();
        /** @var Entry[] $value */
        $entryTypes = $this->getEntryTypesForField($value, $element);

        // Get the entry types data
        $entryTypeInfo = array_map(fn(EntryType $entryType) => [
            'id' => $entryType->id,
            'handle' => $entryType->handle,
            'name' => Craft::t('site', $entryType->name),
        ], $entryTypes);
        $createDefaultEntries = (
            $this->minEntries != 0 &&
            count($entryTypeInfo) === 1 &&
            !$element->hasErrors($this->handle)
        );
        $staticEntries = (
            $createDefaultEntries &&
            $this->minEntries == $this->maxEntries &&
            $this->maxEntries >= count($value)
        );

        $view->registerAssetBundle(MatrixAsset::class);

        $settings = [
            'fieldId' => $this->id,
            'maxEntries' => $this->maxEntries,
            'namespace' => $view->getNamespace(),
            'baseInputName' => $view->namespaceInputName($this->handle),
            'ownerElementType' => $element::class,
            'ownerId' => $element->id,
            'siteId' => $element->siteId,
            'staticEntries' => $staticEntries,
        ];

        $js = 'const input = new Craft.MatrixInput(' .
            '"' . $view->namespaceInputId($id) . '", ' .
            Json::encode($entryTypeInfo) . ', ' .
            '"' . $view->namespaceInputName($this->handle) . '", ' .
            Json::encode($settings) .
            ');';

        // Safe to create the default entries?
        if ($createDefaultEntries && count($value) < $this->minEntries) {
            // @link https://github.com/craftcms/cms/issues/12973
            // for fields with minEntries set Craft.MatrixInput.addEntry() is called before new Craft.ElementEditor(),
            // so when we get our initialSerializedValue() for the ElementEditor,
            // the entry is already there which means the field is reported as not changed since the init
            // and so not passed to PHP for save
            $view->setInitialDeltaValue($this->handle, null);

            $js .= "\n" . <<<JS
input.on('afterInit', async () => {
  if (input.elementEditor) {
    await input.elementEditor.pause();
  }
JS . "\n";

            $entryTypeJs = Json::encode($entryTypes[0]->handle);
            for ($i = count($value); $i < $this->minEntries; $i++) {
                $js .= <<<JS
  await input.addEntry($entryTypeJs, null, false);
JS . "\n";
            }

            $js .= <<<JS
  setTimeout(() => {
    Garnish.requestAnimationFrame(() => {
      input.elementEditor?.resume();
    });
  }, 100);
});
JS;
        }

        $view->registerJs("(() => {\n$js\n})();");

        return $view->renderTemplate('_components/fieldtypes/Matrix/input.twig', [
            'id' => $id,
            'field' => $this,
            'name' => $this->handle,
            'entryTypes' => $entryTypes,
            'entries' => $value,
            'static' => false,
            'staticEntries' => $staticEntries,
            'createButtonLabel' => $this->createButtonLabel(),
            'labelId' => $this->getLabelId(),
        ]);
    }

    private function nestedElementManagerHtml(?ElementInterface $owner, bool $static = false): string
    {
        $entryTypes = $this->getEntryTypes();
        $config = [
            'showInGrid' => $this->showCardsInGrid,
            'prevalidate' => false,
        ];

        if (!$static) {
            $entryTypeIdsJs = Json::encode(array_map(fn(EntryType $entryType) => $entryType->id, $entryTypes));
            $config += [
                'sortable' => true,
                'canCreate' => true,
                'canPaste' => <<<JS
(elementInfo) => {
  const entryTypeIds = $entryTypeIdsJs;
  for (const info of elementInfo) {
    if (!entryTypeIds.includes(info.data.entryTypeId)) {
      return false;
    }
  }
  return true;
}
JS,
                'createAttributes' => array_map(fn(EntryType $entryType) => [
                    'group' => $entryType->group,
                    'icon' => $entryType->icon,
                    'color' => $entryType->color,
                    'label' => Craft::t('site', $entryType->name),
                    'attributes' => [
                        'typeId' => $entryType->id,
                    ],
                ], $entryTypes),
                'createButtonLabel' => $this->createButtonLabel(),
                'minElements' => $this->minEntries,
                'maxElements' => $this->maxEntries,
            ];

            if ($owner->hasErrors($this->handle)) {
                $config['prevalidate'] = true;
            }
        }

        if ($this->viewMode === self::VIEW_MODE_CARDS) {
            return $this->entryManager()->getCardsHtml($owner, $config);
        }

        $config += [
            'allowedViewModes' => array_filter([
                ElementIndexViewMode::Cards,
                $this->includeTableView ? ElementIndexViewMode::Table : null,
            ]),
            'showHeaderColumn' => ArrayHelper::contains($entryTypes, fn(EntryType $entryType) => (
                $entryType->hasTitleField ||
                $entryType->titleFormat
            )),
            'pageSize' => $this->pageSize ?? 50,
            'storageKey' => sprintf('field:%s', $this->uid),
            'defaultViewMode' => $this->defaultIndexViewMode,
        ];

        if (!$static) {
            $config += [
                'fieldLayouts' => array_map(fn(EntryType $entryType) => $entryType->getFieldLayout(), $entryTypes),
                'defaultTableColumns' => array_map(fn(string $attribute) => [$attribute], $this->defaultTableColumns),
            ];
        }

        return $this->entryManager()->getIndexHtml($owner, $config);
    }

    private function createButtonLabel(): string
    {
        if (isset($this->createButtonLabel)) {
            return Craft::t('site', $this->createButtonLabel);
        }
        return $this->defaultCreateButtonLabel();
    }

    private function defaultCreateButtonLabel(): string
    {
        return Craft::t('app', 'New {type}', [
            'type' => Entry::lowerDisplayName(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        return [
            [
                fn(ElementInterface $element) => $this->validateEntries($element),
                'on' => [Element::SCENARIO_ESSENTIALS, Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE],
                'skipOnEmpty' => false,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        /** @var EntryQuery|ElementCollection $value */
        return $value->count() === 0;
    }

    private function validateEntries(ElementInterface $element): void
    {
        /** @var EntryQuery|ElementCollection $value */
        $value = $element->getFieldValue($this->handle);
        $new = 0;

        if ($value instanceof EntryQuery) {
            /** @var Entry[] $entries */
            $entries = $value->getCachedResult() ?? (clone $value)
                ->drafts(null)
                ->savedDraftsOnly()
                ->status(null)
                ->limit(null)
                ->all();

            $invalidEntryIds = [];
            $scenario = $element->getScenario();

            foreach ($entries as $entry) {
                $entry->setOwner($element);

                /** @var Entry $entry */
                if (
                    $scenario === Element::SCENARIO_ESSENTIALS ||
                    ($entry->enabled && $scenario === Element::SCENARIO_LIVE)
                ) {
                    $entry->setScenario($scenario);
                }

                if (!$entry->validate()) {
                    // we only want to show the nested entries errors when the matrix field is in blocks view mode;
                    if ($this->viewMode === self::VIEW_MODE_BLOCKS) {
                        $key = $entry->uid ?? sprintf('new%s', ++$new);
                        $element->addModelErrors($entry, sprintf('%s[%s]', $this->handle, $key));
                    }
                    $invalidEntryIds[] = $entry->id;
                }
            }

            if (!empty($invalidEntryIds)) {
                // Just in case the entries weren't already cached
                $value->setCachedResult($entries);
                $element->addInvalidNestedElementIds($invalidEntryIds);

                if ($this->viewMode !== self::VIEW_MODE_BLOCKS) {
                    // in card/index modes, we want to show a top level error to let users know
                    // that there are validation errors in the nested entries
                    $element->addError($this->handle, Craft::t('app', 'Validation errors found in {count, plural, =1{one nested entry} other{{count, spellout} nested entries}} within the *{fieldName}* field; please fix them.', [
                        'count' => count($invalidEntryIds),
                        'fieldName' => $this->getUiLabel(),
                    ]));
                }
            }
        } else {
            $entries = $value->all();
        }

        if (
            $element->getScenario() === Element::SCENARIO_LIVE &&
            ($this->minEntries || $this->maxEntries)
        ) {
            $arrayValidator = new ArrayValidator([
                'min' => $this->minEntries ?: null,
                'max' => $this->maxEntries ?: null,
                'tooFew' => $this->minEntries ? Craft::t('app', '{attribute} should contain at least {min, number} {min, plural, one{entry} other{entries}}.', [
                    'attribute' => Craft::t('site', $this->name),
                    'min' => $this->minEntries, // Need to pass this in now
                ]) : null,
                'tooMany' => $this->maxEntries ? Craft::t('app', '{attribute} should contain at most {max, number} {max, plural, one{entry} other{entries}}.', [
                    'attribute' => Craft::t('site', $this->name),
                    'max' => $this->maxEntries, // Need to pass this in now
                ]) : null,
                'skipOnEmpty' => false,
            ]);

            if (!$arrayValidator->validate($entries, $error)) {
                $element->addError($this->handle, $error);
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function searchKeywords(mixed $value, ElementInterface $element): string
    {
        return $this->entryManager()->getSearchKeywords($element);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        if ($this->viewMode !== self::VIEW_MODE_BLOCKS) {
            return $this->nestedElementManagerHtml($element, true);
        }

        /** @var EntryQuery|ElementCollection $value */
        $entries = $value->status(null)->all();

        if (empty($entries)) {
            return '<p class="light">' . Craft::t('app', 'No entries.') . '</p>';
        }

        $view = Craft::$app->getView();
        $view->registerAssetBundle(MatrixAsset::class);

        $id = StringHelper::randomString();
        $js = '';

        foreach ($entries as $entry) {
            $js .= <<<JS
Craft.MatrixInput.initTabs($('.matrixblock[data-uid="$entry->uid"] > .titlebar .matrixblock-tabs'));
JS;
        }

        $view->registerJs("(() => {\n$js\n})();");

        return $view->renderTemplate('_components/fieldtypes/Matrix/input.twig', [
            'id' => $id,
            'name' => $id,
            'entryTypes' => $this->getEntryTypes(),
            'entries' => $entries,
            'static' => true,
            'staticEntries' => true,
        ]);
    }

    /**
     * @inheritdoc
     * @return EagerLoadingMap|null|false
     */
    public function getEagerLoadingMap(array $sourceElements): array|null|false
    {
        // Get the source element IDs
        $sourceElementIds = [];

        foreach ($sourceElements as $sourceElement) {
            $sourceElementIds[] = $sourceElement->id;
        }

        // Return any relation data on these elements, defined with this field
        $map = (new Query())
            ->select([
                'source' => 'elements_owners.ownerId',
                'target' => 'entries.id',
            ])
            ->from(['entries' => DbTable::ENTRIES])
            ->innerJoin(['elements_owners' => DbTable::ELEMENTS_OWNERS], [
                'and',
                '[[elements_owners.elementId]] = [[entries.id]]',
                ['elements_owners.ownerId' => $sourceElementIds],
            ])
            ->where(['entries.fieldId' => $this->id])
            ->orderBy(['elements_owners.sortOrder' => SORT_ASC])
            ->all();

        return [
            'elementType' => Entry::class,
            'map' => $map,
            'criteria' => [
                'fieldId' => $this->id,
                'allowOwnerDrafts' => true,
                'allowOwnerRevisions' => true,
                // only include revisions if any of the source elements is a revision
                // see https://github.com/craftcms/cms/issues/14448 and https://github.com/craftcms/cms/issues/17324
                'revisions' => Collection::make($sourceElements)
                    ->contains(fn($sourceElement) => $sourceElement->getIsRevision()),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function canMergeFrom(FieldInterface $outgoingField, ?string &$reason): bool
    {
        if (!$outgoingField instanceof self) {
            $reason = 'Matrix fields can only be merged into other Matrix fields.';
            return false;
        }

        // Make sure this field has all the entry types the outgoing field has
        $outgoingEntryTypeIds = array_map(fn(EntryType $entryType) => $entryType->id, $outgoingField->getEntryTypes());
        $persistentEntryTypeIds = array_map(fn(EntryType $entryType) => $entryType->id, $this->getEntryTypes());
        $missingEntryTypeIds = array_diff($outgoingEntryTypeIds, $persistentEntryTypeIds);
        if (!empty($missingEntryTypeIds)) {
            $reason = "$this->name doesn’t have all of the entry types that $outgoingField->name does.";
            return false;
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterMergeFrom(FieldInterface $outgoingField)
    {
        Db::update(DbTable::ENTRIES, ['fieldId' => $this->id], ['fieldId' => $outgoingField->id]);
        parent::afterMergeFrom($outgoingField);
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getContentGqlType(): Type|array
    {
        $typeArray = EntryTypeGenerator::generateTypes($this);
        $typeName = $this->handle . '_MatrixField';

        $arguments = EntryArguments::getArguments();
        $gqlService = Craft::$app->getGql();

        foreach ($this->getEntryTypes() as $entryType) {
            $arguments += $gqlService->getFieldLayoutArguments($entryType->getFieldLayout());
        }

        return [
            'name' => $this->handle,
            'type' => Type::nonNull(Type::listOf(Gql::getUnionType($typeName, $typeArray))),
            'args' => $arguments,
            'resolve' => EntryResolver::class . '::resolve',
            'complexity' => Gql::eagerLoadComplexity(),
        ];
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getContentGqlMutationArgumentType(): Type|array
    {
        return MatrixInputType::getType($this);
    }

    /**
     * @inheritdoc
     * @throws InvalidArgumentException
     * @since 3.3.0
     */
    public function getGqlFragmentEntityByName(string $fragmentName): GqlInlineFragmentInterface
    {
        $entryTypeHandle = StringHelper::removeLeft(StringHelper::removeRight($fragmentName, '_Entry'), $this->handle . '_');

        $entryType = ArrayHelper::firstWhere($this->getEntryTypes(), 'handle', $entryTypeHandle);

        if (!$entryType) {
            throw new InvalidArgumentException('Invalid fragment name: ' . $fragmentName);
        }

        return $entryType;
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew): void
    {
        // If the propagation method or an entry URI format just changed, resave all the entries
        if (isset($this->oldSettings)) {
            $oldPropagationMethod = PropagationMethod::tryFrom($this->oldSettings['propagationMethod'] ?? '')
                ?? PropagationMethod::All;
            $oldPropagationKeyFormat = $this->oldSettings['propagationKeyFormat'] ?? null;
            if ($this->propagationMethod !== $oldPropagationMethod || $this->propagationKeyFormat !== $oldPropagationKeyFormat) {
                Queue::push(new ApplyNewPropagationMethod([
                    'description' => Translation::prep('app', 'Applying new propagation method to {name} entries', [
                        'name' => $this->name,
                    ]),
                    'elementType' => Entry::class,
                    'criteria' => [
                        'fieldId' => $this->id,
                    ],
                ]));
            } else {
                $resaveSiteIds = [];

                foreach (Craft::$app->getSites()->getAllSites(true) as $site) {
                    $oldUriFormat = $this->oldSettings['siteSettings'][$site->uid]['uriFormat'] ?? null;
                    $newUriFormat = $this->siteSettings[$site->uid]['uriFormat'] ?? null;
                    if ($oldUriFormat !== $newUriFormat) {
                        $resaveSiteIds[] = $site->id;
                    }
                }

                if (!empty($resaveSiteIds)) {
                    Queue::push(new ResaveElements([
                        'description' => Translation::prep('app', 'Resaving {name} entries', [
                            'name' => $this->name,
                        ]),
                        'elementType' => Entry::class,
                        'criteria' => [
                            'fieldId' => $this->id,
                            'siteId' => $resaveSiteIds,
                            'unique' => true,
                            'status' => null,
                            'drafts' => null,
                            'provisionalDrafts' => null,
                            'revisions' => null,
                        ],
                    ]));
                }
            }
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterElementPropagate(ElementInterface $element, bool $isNew): void
    {
        $this->entryManager()->maintainNestedElements($element, $isNew);
        parent::afterElementPropagate($element, $isNew);
    }

    /**
     * Handles nested entry saves.
     *
     * @param BulkElementsEvent $event
     * @since 5.0.0
     */
    public function afterSaveEntries(BulkElementsEvent $event): void
    {
        if (
            !Craft::$app->getRequest()->getIsConsoleRequest() &&
            !Craft::$app->getResponse()->isSent
        ) {
            // Tell the browser to collapse any new entry IDs
            $collapsedIds = Collection::make($event->elements)
                /** @phpstan-ignore-next-line */
                ->filter(fn(Entry $entry) => $entry->collapsed)
                /** @phpstan-ignore-next-line */
                ->map(fn(Entry $entry) => $entry->id)
                ->all();

            if (!empty($collapsedIds)) {
                Craft::$app->getSession()->addAssetBundleFlash(MatrixAsset::class);

                foreach ($collapsedIds as $id) {
                    Craft::$app->getSession()->addJsFlash("Craft.MatrixInput.rememberCollapsedEntryId($id);", View::POS_END);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeElementDelete(ElementInterface $element): bool
    {
        if (!parent::beforeElementDelete($element)) {
            return false;
        }

        // Delete any entries that primarily belong to this element
        $this->entryManager()->deleteNestedElements($element, $element->hardDelete);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function beforeElementDeleteForSite(ElementInterface $element): bool
    {
        $elementsService = Craft::$app->getElements();

        /** @var Entry[] $entries */
        $entries = Entry::find()
            ->primaryOwnerId($element->id)
            ->status(null)
            ->siteId($element->siteId)
            ->all();

        foreach ($entries as $entry) {
            $elementsService->deleteElementForSite($entry);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterElementRestore(ElementInterface $element): void
    {
        // Also restore any entries for this element
        $this->entryManager()->restoreNestedElements($element);

        parent::afterElementRestore($element);
    }

    /**
     * Creates an array of entries based on the given serialized data.
     *
     * @param array $value The raw field value
     * @param ElementInterface $element The element the field is associated with
     * @param bool $fromRequest Whether the data came from the request post data
     * @return Entry[]
     */
    private function _createEntriesFromSerializedData(array $value, ElementInterface $element, bool $fromRequest): array
    {
        // Get the possible entry types for this field
        /** @var EntryType[] $entryTypes */
        $entryTypes = ArrayHelper::index($this->getEntryTypes(), 'handle');

        // Were the entries posted by UUID or ID?
        $uids = (
            (isset($value['entries']) && str_starts_with(array_key_first($value['entries']), 'uid:')) ||
            (isset($value['sortOrder']) && StringHelper::isUUID(reset($value['sortOrder'])))
        );

        if ($uids) {
            // strip out the `uid:` key prefixes
            if (isset($value['entries'])) {
                $value['entries'] = array_combine(
                    array_map(fn(string $key) => StringHelper::removeLeft($key, 'uid:'), array_keys($value['entries'])),
                    array_values($value['entries']),
                );
            }
        }

        // Get the old entries
        if ($element->id) {
            /** @var Entry[] $oldEntriesById */
            $oldEntriesById = Entry::find()
                ->fieldId($this->id)
                ->ownerId($element->id)
                ->siteId($element->siteId)
                ->drafts(null)
                ->status(null)
                ->indexBy($uids ? 'uid' : 'id')
                ->all();
        } else {
            $oldEntriesById = [];
        }

        if ($uids) {
            // Get the canonical entry UUIDs in case the data was posted with them
            $derivatives = Collection::make($oldEntriesById)
                ->filter(fn(Entry $entry) => $entry->getIsDerivative())
                ->keyBy(fn(Entry $entry) => $entry->getCanonicalId());

            if ($derivatives->isNotEmpty()) {
                $canonicalUids = (new Query())
                    ->select(['id', 'uid'])
                    ->from(DbTable::ELEMENTS)
                    ->where(['id' => $derivatives->keys()->all()])
                    ->pairs();
                $derivativeUidMap = [];
                $canonicalUidMap = [];
                foreach ($canonicalUids as $canonicalId => $canonicalUid) {
                    $derivativeUid = $derivatives->get($canonicalId)->uid;
                    $derivativeUidMap[$canonicalUid] = $derivativeUid;
                    $canonicalUidMap[$derivativeUid] = $canonicalUid;
                }
            }
        }

        // Should we ignore disabled entries?
        $request = Craft::$app->getRequest();
        $hideDisabledEntries = !$request->getIsConsoleRequest() && (
                $request->getToken() !== null ||
                $request->getIsLivePreview()
            );

        $entries = [];
        $prevEntry = null;

        $fieldNamespace = $element->getFieldParamNamespace();
        $baseEntryFieldNamespace = $fieldNamespace ? "$fieldNamespace.$this->handle" : null;

        // Was the value posted in the new (delta) format?
        if (isset($value['entries']) || isset($value['blocks']) || isset($value['sortOrder'])) {
            $newEntryData = $value['entries'] ?? $value['blocks'] ?? [];
            $newSortOrder = $value['sortOrder'] ?? array_keys($oldEntriesById);
            if ($baseEntryFieldNamespace) {
                $baseEntryFieldNamespace .= '.entries';
            }
        } else {
            $newEntryData = $value;
            $newSortOrder = array_keys($value);
        }

        foreach ($newSortOrder as $entryId) {
            if (isset($newEntryData[$entryId])) {
                $entryData = $newEntryData[$entryId];
            } elseif (
                $uids &&
                isset($canonicalUidMap[$entryId]) &&
                isset($newEntryData[$canonicalUidMap[$entryId]])
            ) {
                // $entryId is a draft entry's UUID, but the data was sent with the canonical entry UUID
                $entryData = $newEntryData[$canonicalUidMap[$entryId]];
            } else {
                $entryData = [];
            }

            // If this is a preexisting entry but we don't have a record of it,
            // check to see if it was recently duplicated.
            if (
                $uids &&
                !isset($oldEntriesById[$entryId]) &&
                isset($derivativeUidMap[$entryId]) &&
                isset($oldEntriesById[$derivativeUidMap[$entryId]])
            ) {
                $entryId = $derivativeUidMap[$entryId];
            }

            // Existing entry?
            if (isset($oldEntriesById[$entryId])) {
                $entry = $oldEntriesById[$entryId];
                $forceSave = !empty($entryData);

                // Is this a derivative element, and does the entry primarily belong to the canonical?
                $request = Craft::$app->getRequest();
                if (
                    $forceSave &&
                    $element->getIsDerivative() &&
                    $entry->getPrimaryOwnerId() === $element->getCanonicalId() &&
                    // this is so that extra drafts don't get created for matrix in matrix scenario
                    // where both are set to inline-editable blocks view mode
                    (
                        $request->getIsConsoleRequest() ||
                        $request->getActionSegments() !== ['elements', 'update-field-layout']
                    )
                ) {
                    // Duplicate it as a draft. (We'll drop its draft status from NestedElementManager::saveNestedElements().)
                    $entry = Craft::$app->getDrafts()->createDraft($entry, Craft::$app->getUser()->getId(), null, null, [
                        'canonicalId' => $entry->id,
                        'primaryOwnerId' => $element->id,
                        'owner' => $element,
                        'siteId' => $element->siteId,
                        'propagating' => false,
                        'markAsSaved' => false,
                    ]);
                }

                $entry->forceSave = $forceSave;
            } else {
                // Make sure it's a valid entry type
                if (!isset($entryData['type']) || !isset($entryTypes[$entryData['type']])) {
                    continue;
                }
                $entry = new Entry();
                $entry->fieldId = $this->id;
                $entry->typeId = $entryTypes[$entryData['type']]->id;
                $entry->setPrimaryOwner($element);
                $entry->setOwner($element);
                $entry->siteId = $element->siteId;

                // Use the provided UUID, so the block can persist across future autosaves
                if ($uids) {
                    $entry->uid = $entryId;
                }

                // Preserve the collapsed state, which the browser can't remember on its own for new entries
                $entry->collapsed = !empty($entryData['collapsed']);
            }

            if (isset($entryData['enabled'])) {
                $entry->enabled = (bool)$entryData['enabled'];
            }

            if (isset($entryData['fresh'])) {
                $entry->setIsFresh();
                $entry->propagateAll = true;
            }

            if (isset($entryData['title']) && $entry->getType()->hasTitleField) {
                $entry->title = $entryData['title'];
            }

            if (isset($entryData['slug']) && $entry->getType()->showSlugField) {
                $entry->slug = $entryData['slug'];
            }

            // Allow setting the UID for the entry
            if (isset($entryData['uid'])) {
                $entry->uid = $entryData['uid'];
            }

            // Skip disabled entries on Live Preview requests
            if ($hideDisabledEntries && !$entry->enabled) {
                continue;
            }

            $entry->setOwner($element);

            // Set the content post location on the entry if we can
            if ($baseEntryFieldNamespace) {
                if ($uids) {
                    $entry->setFieldParamNamespace("$baseEntryFieldNamespace.uid:$entryId.fields");
                } else {
                    $entry->setFieldParamNamespace("$baseEntryFieldNamespace.$entryId.fields");
                }
            }

            if (isset($entryData['fields'])) {
                foreach ($entryData['fields'] as $fieldHandle => $fieldValue) {
                    try {
                        if ($fromRequest) {
                            $entry->setFieldValueFromRequest($fieldHandle, $fieldValue);
                        } else {
                            $entry->setFieldValue($fieldHandle, $fieldValue);
                        }
                    } catch (InvalidFieldException) {
                    }
                }
            }

            // Set the prev/next entries
            if ($prevEntry) {
                /** @var ElementInterface $prevEntry */
                $prevEntry->setNext($entry);
                /** @var ElementInterface $entry */
                $entry->setPrev($prevEntry);
            }
            $prevEntry = $entry;

            $entries[] = $entry;
        }

        /** @var Entry[] $entries */
        return $entries;
    }
}
