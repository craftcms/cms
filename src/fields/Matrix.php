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
use craft\base\GqlInlineFragmentFieldInterface;
use craft\base\GqlInlineFragmentInterface;
use craft\base\NestedElementInterface;
use craft\behaviors\EventBehavior;
use craft\db\Query;
use craft\db\Table;
use craft\db\Table as DbTable;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\EntryQuery;
use craft\elements\ElementCollection;
use craft\elements\Entry;
use craft\errors\InvalidFieldException;
use craft\events\CancelableEvent;
use craft\events\DefineEntryTypesForFieldEvent;
use craft\fields\conditions\EmptyFieldConditionRule;
use craft\gql\arguments\elements\Entry as EntryArguments;
use craft\gql\resolvers\elements\Entry as EntryResolver;
use craft\gql\types\generators\EntryType as EntryTypeGenerator;
use craft\gql\types\input\Matrix as MatrixInputType;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\Gql;
use craft\helpers\Json;
use craft\helpers\Queue;
use craft\helpers\StringHelper;
use craft\i18n\Translation;
use craft\models\EntryType;
use craft\models\Site;
use craft\queue\jobs\ApplyNewPropagationMethod;
use craft\services\Elements;
use craft\validators\ArrayValidator;
use craft\web\assets\matrix\MatrixAsset;
use craft\web\View;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
use Throwable;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\db\Expression;

/**
 * Matrix field type
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Matrix extends Field implements
    ElementContainerFieldInterface,
    EagerLoadingFieldInterface,
    GqlInlineFragmentFieldInterface
{
    /**
     * @event DefineEntryTypesForFieldEvent The event that is triggered when defining the available entry types.
     * @since 5.0.0
     */
    public const EVENT_DEFINE_ENTRY_TYPES = 'defineEntryTypes';

    public const PROPAGATION_METHOD_NONE = 'none';
    public const PROPAGATION_METHOD_SITE_GROUP = 'siteGroup';
    public const PROPAGATION_METHOD_LANGUAGE = 'language';
    /**
     * @since 3.7.0
     */
    public const PROPAGATION_METHOD_CUSTOM = 'custom';
    public const PROPAGATION_METHOD_ALL = 'all';

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
            ->innerJoin(["entries_owners_$ns" => DbTable::ENTRIES_OWNERS], "[[entries_owners_$ns.entryId]] = [[elements_$ns.id]]")
            ->andWhere([
                "entries_$ns.fieldId" => $field->id,
                "elements_$ns.enabled" => true,
                "elements_$ns.dateDeleted" => null,
                "[[entries_owners_$ns.ownerId]]" => new Expression('[[elements.id]]'),
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

            $ids = array_map(function($id) {
                return $id instanceof Entry ? $id->id : (int)$id;
            }, $ids);

            $existsQuery->andWhere(["entries_$ns.id" => $ids]);
        }

        return ['exists', $existsQuery];
    }

    /**
     * Returns the site IDs that are supported by entries for the given propagation method and owner element.
     *
     * @param string $propagationMethod
     * @param ElementInterface $owner
     * @param string|null $propagationKeyFormat
     * @return int[]
     */
    public static function supportedSiteIds(
        string $propagationMethod,
        ElementInterface $owner,
        ?string $propagationKeyFormat = null,
    ): array {
        /** @var Site[] $allSites */
        $allSites = ArrayHelper::index(Craft::$app->getSites()->getAllSites(), 'id');
        $ownerSiteIds = ArrayHelper::getColumn(ElementHelper::supportedSitesForElement($owner), 'siteId');
        $siteIds = [];

        $view = Craft::$app->getView();
        $elementsService = Craft::$app->getElements();

        if ($propagationMethod === self::PROPAGATION_METHOD_CUSTOM && $propagationKeyFormat !== null) {
            $propagationKey = $view->renderObjectTemplate($propagationKeyFormat, $owner);
        }

        foreach ($ownerSiteIds as $siteId) {
            switch ($propagationMethod) {
                case self::PROPAGATION_METHOD_NONE:
                    $include = $siteId == $owner->siteId;
                    break;
                case self::PROPAGATION_METHOD_SITE_GROUP:
                    $include = $allSites[$siteId]->groupId == $allSites[$owner->siteId]->groupId;
                    break;
                case self::PROPAGATION_METHOD_LANGUAGE:
                    $include = $allSites[$siteId]->language == $allSites[$owner->siteId]->language;
                    break;
                case self::PROPAGATION_METHOD_CUSTOM:
                    if (!isset($propagationKey)) {
                        $include = true;
                    } else {
                        $siteOwner = $elementsService->getElementById($owner->id, get_class($owner), $siteId);
                        $include = $siteOwner && $propagationKey === $view->renderObjectTemplate($propagationKeyFormat, $siteOwner);
                    }
                    break;
                default:
                    $include = true;
                    break;
            }

            if ($include) {
                $siteIds[] = $siteId;
            }
        }

        return $siteIds;
    }

    /**
     * @var int|null Min entries
     */
    public ?int $minEntries = null;

    /**
     * @var int|null Max entries
     */
    public ?int $maxEntries = null;

    /**
     * @var string|null Entry URI format
     * @since 5.0.0
     */
    public ?string $entryUriFormat = null;

    /**
     * @var string Propagation method
     * @phpstan-var self::PROPAGATION_METHOD_NONE|self::PROPAGATION_METHOD_SITE_GROUP|self::PROPAGATION_METHOD_LANGUAGE|self::PROPAGATION_METHOD_ALL|self::PROPAGATION_METHOD_CUSTOM
     *
     * This will be set to one of the following:
     *
     * - `none` – Only save entries in the site they were created in
     * - `siteGroup` – Save  entries to other sites in the same site group
     * - `language` – Save entries to other sites with the same language
     * - `all` – Save entries to all sites supported by the owner element
     *
     * @since 3.2.0
     */
    public string $propagationMethod = self::PROPAGATION_METHOD_ALL;

    /**
     * @var string|null The field’s propagation key format, if [[propagationMethod]] is `custom`
     * @since 3.7.0
     */
    public ?string $propagationKeyFormat = null;

    /**
     * @var EntryType[] The field’s available entry types
     * @see getEntryTypes()
     * @see setEntryTypes()
     */
    private array $_entryTypes = [];

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // Config normalization
        unset($config['contentTable']);

        if (array_key_exists('localizeEntries', $config)) {
            $config['propagationMethod'] = $config['localizeEntries'] ? 'none' : 'all';
            unset($config['localizeEntries']);
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
        $settings['entryTypes'] = array_map(fn(EntryType $entryType) => $entryType->uid, $this->_entryTypes);
        return $settings;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [
            ['propagationMethod'], 'in', 'range' => [
                self::PROPAGATION_METHOD_NONE,
                self::PROPAGATION_METHOD_SITE_GROUP,
                self::PROPAGATION_METHOD_LANGUAGE,
                self::PROPAGATION_METHOD_CUSTOM,
                self::PROPAGATION_METHOD_ALL,
            ],
        ];
        $rules[] = [['entryTypes'], ArrayValidator::class, 'min' => 1, 'skipOnEmpty' => false];
        $rules[] = [['minEntries', 'maxEntries'], 'integer', 'min' => 0];
        return $rules;
    }

    /**
     * Returns the available entry types.
     */
    public function getEntryTypes(): array
    {
        return $this->_entryTypes;
    }

    /**
     * Sets the available entry types.
     *
     * @param array<int|string|EntryType> $entryTypes The entry types, or their IDs or UUIDs
     */
    public function setEntryTypes(array $entryTypes): void
    {
        $entriesService = Craft::$app->getEntries();

        $this->_entryTypes = array_map(function(EntryType|string|int $entryType) use ($entriesService) {
            if (is_numeric($entryType)) {
                $entryType = $entriesService->getEntryTypeById($entryType);
                if (!$entryType) {
                    throw new InvalidArgumentException("Invalid entry type ID: $entryType");
                }
            } elseif (is_string($entryType)) {
                $entryTypeUid = $entryType;
                $entryType = $entriesService->getEntryTypeByUid($entryTypeUid);
                if (!$entryType) {
                    throw new InvalidArgumentException("Invalid entry type UUID: $entryTypeUid");
                }
            } elseif (!$entryType instanceof EntryType) {
                throw new InvalidArgumentException('Invalid entry type');
            }
            return $entryType;
        }, $entryTypes);
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
        return $this->entryUriFormat;
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

        return self::supportedSiteIds($this->propagationMethod, $owner, $this->propagationKeyFormat);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        $entryTypeOptions = array_map(
            fn(EntryType $entryType) => [
                'label' => Craft::t('site', $entryType->name),
                'value' => $entryType->id,
            ],
            $this->getEntryTypes(),
        );
        usort($entryTypeOptions, fn(array $a, array $b) => $a['label'] <=> $b['label']);

        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Matrix/settings.twig', [
            'field' => $this,
            'entryTypeOptions' => $entryTypeOptions,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        return $this->_normalizeValueInternal($value, $element, false);
    }

    /**
     * @inheritdoc
     */
    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element = null): mixed
    {
        return $this->_normalizeValueInternal($value, $element, true);
    }

    private function _normalizeValueInternal(mixed $value, ?ElementInterface $element, bool $fromRequest): mixed
    {
        if ($value instanceof ElementQueryInterface) {
            return $value;
        }

        $query = Entry::find();
        $this->_populateQuery($query, $element);

        // Set the initially matched elements if $value is already set, which is the case if there was a validation
        // error or we're loading an entry revision.
        if ($value === '') {
            $query->setCachedResult([]);
        } elseif ($element && is_array($value)) {
            $query->setCachedResult($this->_createEntriesFromSerializedData($value, $element, $fromRequest));
        }

        return $query;
    }

    /**
     * Populates the field’s [[EntryQuery]] value based on the owner element.
     *
     * @param EntryQuery $query
     * @param ElementInterface|null $element
     */
    private function _populateQuery(EntryQuery $query, ?ElementInterface $element = null): void
    {
        // Existing element?
        if ($element && $element->id) {
            $query->attachBehavior(self::class, new EventBehavior([
                ElementQuery::EVENT_BEFORE_PREPARE => function(
                    CancelableEvent $event,
                    EntryQuery $query,
                ) use ($element) {
                    $query->ownerId = $element->id;

                    // Clear out id=false if this query was populated previously
                    if ($query->id === false) {
                        $query->id = null;
                    }

                    // If the owner is a revision, allow revision entries to be returned as well
                    if ($element->getIsRevision()) {
                        $query
                            ->revisions(null)
                            ->trashed(null);
                    }
                },
            ], true));

            // Set the query up for lazy eager loading
            $query->eagerLoadSourceElement = $element;
            $providerHandle = $element->getFieldLayout()?->provider?->getHandle();
            $query->eagerLoadHandle = $providerHandle ? "$providerHandle:$this->handle" : $this->handle;
        } else {
            $query->id = false;
        }

        $query
            ->fieldId($this->id)
            ->siteId($element->siteId ?? null);
    }

    /**
     * @inheritdoc
     */
    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        /** @var EntryQuery|Collection $value */
        $serialized = [];
        $new = 0;

        foreach ($value->all() as $entry) {
            $entryId = $entry->id ?? 'new' . ++$new;
            $serialized[$entryId] = [
                'type' => $entry->getType()->handle,
                'enabled' => $entry->enabled,
                'collapsed' => $entry->collapsed,
                'fields' => fn() => $entry->getSerializedFieldValues(),
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
    public function getIsTranslatable(?ElementInterface $element = null): bool
    {
        if ($this->propagationMethod === self::PROPAGATION_METHOD_CUSTOM) {
            return (
                $element === null ||
                Craft::$app->getView()->renderObjectTemplate($this->propagationKeyFormat, $element) !== ''
            );
        }

        return $this->propagationMethod !== self::PROPAGATION_METHOD_ALL;
    }

    /**
     * @inheritdoc
     */
    public function getTranslationDescription(?ElementInterface $element = null): ?string
    {
        if (!$element) {
            return null;
        }

        switch ($this->propagationMethod) {
            case self::PROPAGATION_METHOD_NONE:
                return Craft::t('app', 'Entries will only be saved in the {site} site.', [
                    'site' => Craft::t('site', $element->getSite()->getName()),
                ]);
            case self::PROPAGATION_METHOD_SITE_GROUP:
                return Craft::t('app', 'Entries will be saved across all sites in the {group} site group.', [
                    'group' => Craft::t('site', $element->getSite()->getGroup()->getName()),
                ]);
            case self::PROPAGATION_METHOD_LANGUAGE:
                $language = Craft::$app->getI18n()->getLocaleById($element->getSite()->language)
                    ->getDisplayName(Craft::$app->language);
                return Craft::t('app', 'Entries will be saved across all {language}-language sites.', [
                    'language' => $language,
                ]);
            default:
                return null;
        }
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        if ($element !== null && $element->hasEagerLoadedElements($this->handle)) {
            $value = $element->getEagerLoadedElements($this->handle)->all();
        }

        if ($value instanceof EntryQuery) {
            $value = $value->getCachedResult() ?? $value->limit(null)->status(null)->all();
        }

        $view = Craft::$app->getView();
        $id = $this->getInputId();

        // Let plugins/modules override which entry types should be available for this field
        $event = new DefineEntryTypesForFieldEvent([
            'entryTypes' => $this->getEntryTypes(),
            'element' => $element,
            'value' => $value,
        ]);
        $this->trigger(self::EVENT_DEFINE_ENTRY_TYPES, $event);
        $entryTypes = array_values($event->entryTypes);

        if (empty($entryTypes)) {
            throw new InvalidConfigException('At least one entry type is required.');
        }

        // Get the entry types data
        $placeholderKey = StringHelper::randomString(10);
        $entryTypeInfo = $this->_getEntryTypeInfoForInput($element, $entryTypes, $placeholderKey);
        $createDefaultEntries = (
            $this->minEntries != 0 &&
            count($entryTypeInfo) === 1 &&
            (!$element || !$element->hasErrors($this->handle))
        );
        $staticEntries = (
            $createDefaultEntries &&
            $this->minEntries == $this->maxEntries &&
            $this->maxEntries >= count($value)
        );

        $view->registerAssetBundle(MatrixAsset::class);

        $settings = [
            'placeholderKey' => $placeholderKey,
            'maxEntries' => $this->maxEntries,
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

            $entryTypeJs = Json::encode($entryTypes[0]->handle);
            for ($i = count($value); $i < $this->minEntries; $i++) {
                $js .= "\ninput.addEntry($entryTypeJs, null, false);";
            }
        }

        $view->registerJs($js);

        return $view->renderTemplate('_components/fieldtypes/Matrix/input.twig',
            [
                'id' => $id,
                'name' => $this->handle,
                'entryTypes' => $entryTypes,
                'entries' => $value,
                'static' => false,
                'staticEntries' => $staticEntries,
                'labelId' => $this->getLabelId(),
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        return [
            [
                'validateEntries',
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
        /** @var EntryQuery|Collection $value */
        return $value->count() === 0;
    }

    /**
     * Validates an owner element’s nested entries.
     *
     * @param ElementInterface $element
     */
    public function validateEntries(ElementInterface $element): void
    {
        /** @var EntryQuery|Collection $value */
        $value = $element->getFieldValue($this->handle);

        if ($value instanceof EntryQuery) {
            $entries = $value->getCachedResult() ?? (clone $value)->status(null)->limit(null)->all();

            $allEntriesValidate = true;
            $scenario = $element->getScenario();

            foreach ($entries as $i => $entry) {
                /** @var Entry $entry */
                if (
                    $scenario === Element::SCENARIO_ESSENTIALS ||
                    ($entry->enabled && $scenario === Element::SCENARIO_LIVE)
                ) {
                    $entry->setScenario($scenario);
                }

                // Don't validate the title if the entry type has a dynamic title format
                if (!$entry->getType()->hasTitleField) {
                    $attributes = ArrayHelper::withoutValue($entry->activeAttributes(), 'title');
                } else {
                    $attributes = null;
                }

                if (!$entry->validate($attributes)) {
                    $element->addModelErrors($entry, "$this->handle[$i]");
                    $allEntriesValidate = false;
                }
            }

            if (!$allEntriesValidate) {
                // Just in case the entries weren't already cached
                $value->setCachedResult($entries);
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
        /** @var EntryQuery|Collection $value */
        $keywords = [];

        foreach ($value->all() as $entry) {
            foreach ($entry->getFieldLayout()->getCustomFields() as $field) {
                if ($field->searchable) {
                    $fieldValue = $entry->getFieldValue($field->handle);
                    $keywords[] = $field->getSearchKeywords($fieldValue, $element);
                }
            }
        }

        return parent::searchKeywords($keywords, $element);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        /** @var EntryQuery|Collection $value */
        $entries = $value->all();

        if (empty($entries)) {
            return '<p class="light">' . Craft::t('app', 'No entries.') . '</p>';
        }

        $id = StringHelper::randomString();

        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Matrix/input.twig', [
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
                'source' => 'entries_owners.ownerId',
                'target' => 'entries.id',
            ])
            ->from(['entries' => DbTable::ENTRIES])
            ->innerJoin(['entries_owners' => DbTable::ENTRIES_OWNERS], [
                'and',
                '[[entries_owners.entryId]] = [[entries.id]]',
                ['entries_owners.ownerId' => $sourceElementIds],
            ])
            ->where(['entries.fieldId' => $this->id])
            ->orderBy(['entries_owners.sortOrder' => SORT_ASC])
            ->all();

        return [
            'elementType' => Entry::class,
            'map' => $map,
            'criteria' => [
                'fieldId' => $this->id,
                'allowOwnerDrafts' => true,
                'allowOwnerRevisions' => true,
            ],
        ];
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getContentGqlType(): Type|array
    {
        $typeArray = EntryTypeGenerator::generateTypes($this);
        $typeName = $this->handle . '_MatrixField';

        return [
            'name' => $this->handle,
            'type' => Type::nonNull(Type::listOf(Gql::getUnionType($typeName, $typeArray))),
            'args' => EntryArguments::getArguments(),
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
        $entryTypeHandle = StringHelper::removeLeft(StringHelper::removeRight($fragmentName, '_EntryType'), $this->handle . '_');

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
        // If the propagation method just changed, resave all the entries
        if (isset($this->oldSettings)) {
            $oldPropagationMethod = $this->oldSettings['propagationMethod'] ?? self::PROPAGATION_METHOD_ALL;
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
            }
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterElementPropagate(ElementInterface $element, bool $isNew): void
    {
        $resetValue = false;

        if ($element->duplicateOf !== null) {
            // If this is a draft, just duplicate the relations
            if ($element->getIsDraft()) {
                $this->_duplicateOwnership($element->duplicateOf, $element);
            } elseif ($element->getIsRevision()) {
                $this->_createRevisionEntries($element->duplicateOf, $element);
            } else {
                $this->_duplicateEntries($element->duplicateOf, $element, true, !$isNew);
            }
            $resetValue = true;
        } elseif ($element->isFieldDirty($this->handle) || !empty($element->newSiteIds)) {
            $this->_saveEntries($element);
        } elseif ($element->mergingCanonicalChanges) {
            $this->_mergeCanonicalChanges($element);
            $resetValue = true;
        }

        // Repopulate the entry query if this is a new element
        if ($resetValue || $isNew) {
            /** @var EntryQuery|Collection $value */
            $value = $element->getFieldValue($this->handle);
            if ($value instanceof EntryQuery) {
                $this->_populateQuery($value, $element);
                $value->clearCachedResult();
            }
        }

        parent::afterElementPropagate($element, $isNew);
    }

    /**
     * Saves the field’s entries.
     *
     * @param ElementInterface $owner The element the field is associated with
     * @throws Throwable if reasons
     */
    private function _saveEntries(ElementInterface $owner): void
    {
        $elementsService = Craft::$app->getElements();

        /** @var EntryQuery|Collection $value */
        $value = $owner->getFieldValue($this->handle);
        if ($value instanceof Collection) {
            $entries = $value->all();
            $saveAll = true;
        } else {
            $entries = $value->getCachedResult();
            if ($entries !== null) {
                $saveAll = false;
            } else {
                $entries = (clone $value)->status(null)->all();
                $saveAll = true;
            }
        }

        $entryIds = [];
        $collapsedEntryIds = [];
        $sortOrder = 0;

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            /** @var Entry[] $entries */
            foreach ($entries as $entry) {
                $sortOrder++;
                if ($saveAll || !$entry->id || $entry->dirty) {
                    $entry->setOwner($owner);
                    // If the entry already has an ID and primary owner ID, don't reassign it
                    if (!$entry->id || !$entry->primaryOwnerId) {
                        $entry->primaryOwnerId = $owner->id;
                    }
                    $entry->sortOrder = $sortOrder;
                    $elementsService->saveElement($entry, false);

                    // If this is a draft, we can shed the draft data now
                    if ($entry->getIsDraft()) {
                        $canonicalEntryId = $entry->getCanonicalId();
                        Craft::$app->getDrafts()->removeDraftData($entry);
                        Db::delete(Table::ENTRIES_OWNERS, [
                            'entryId' => $canonicalEntryId,
                            'ownerId' => $owner->id,
                        ]);
                    }
                } elseif ((int)$entry->sortOrder !== $sortOrder) {
                    // Just update its sortOrder
                    $entry->sortOrder = $sortOrder;
                    Db::update(Table::ENTRIES_OWNERS, [
                        'sortOrder' => $sortOrder,
                    ], [
                        'entryId' => $entry->id,
                        'ownerId' => $owner->id,
                    ], [], false);
                }

                $entryIds[] = $entry->id;

                // Tell the browser to collapse this entry?
                if ($entry->collapsed) {
                    $collapsedEntryIds[] = $entry->id;
                }
            }

            // Delete any entries that shouldn't be there anymore
            $this->_deleteOtherEntries($owner, $entryIds);

            // Should we duplicate the entries to other sites?
            if (
                $this->propagationMethod !== self::PROPAGATION_METHOD_ALL &&
                ($owner->propagateAll || !empty($owner->newSiteIds))
            ) {
                // Find the owner's site IDs that *aren't* supported by this site's entries
                $ownerSiteIds = ArrayHelper::getColumn(ElementHelper::supportedSitesForElement($owner), 'siteId');
                $fieldSiteIds = self::supportedSiteIds($this->propagationMethod, $owner, $this->propagationKeyFormat);
                $otherSiteIds = array_diff($ownerSiteIds, $fieldSiteIds);

                // If propagateAll isn't set, only deal with sites that the element was just propagated to for the first time
                if (!$owner->propagateAll) {
                    $preexistingOtherSiteIds = array_diff($otherSiteIds, $owner->newSiteIds);
                    $otherSiteIds = array_intersect($otherSiteIds, $owner->newSiteIds);
                } else {
                    $preexistingOtherSiteIds = [];
                }

                if (!empty($otherSiteIds)) {
                    // Get the owner element across each of those sites
                    $localizedOwners = $owner::find()
                        ->drafts($owner->getIsDraft())
                        ->provisionalDrafts($owner->isProvisionalDraft)
                        ->revisions($owner->getIsRevision())
                        ->id($owner->id)
                        ->siteId($otherSiteIds)
                        ->status(null)
                        ->all();

                    // Duplicate entries, ensuring we don't process the same entries more than once
                    $handledSiteIds = [];

                    if ($value instanceof EntryQuery) {
                        $cachedQuery = (clone $value)->status(null);
                        $cachedQuery->setCachedResult($entries);
                        $owner->setFieldValue($this->handle, $cachedQuery);
                    }

                    foreach ($localizedOwners as $localizedOwner) {
                        // Make sure we haven't already duplicated entries for this site, via propagation from another site
                        if (isset($handledSiteIds[$localizedOwner->siteId])) {
                            continue;
                        }

                        // Find all of the field’s supported sites shared with this target
                        $sourceSupportedSiteIds = self::supportedSiteIds($this->propagationMethod, $localizedOwner, $this->propagationKeyFormat);

                        // Do entries in this target happen to share supported sites with a preexisting site?
                        if (
                            !empty($preexistingOtherSiteIds) &&
                            !empty($sharedPreexistingOtherSiteIds = array_intersect($preexistingOtherSiteIds, $sourceSupportedSiteIds)) &&
                            $preexistingLocalizedOwner = $owner::find()
                                ->drafts($owner->getIsDraft())
                                ->provisionalDrafts($owner->isProvisionalDraft)
                                ->revisions($owner->getIsRevision())
                                ->id($owner->id)
                                ->siteId($sharedPreexistingOtherSiteIds)
                                ->status(null)
                                ->one()
                        ) {
                            // Just resave entries for that one site, and let them propagate over to the new site(s) from there
                            $this->_saveEntries($preexistingLocalizedOwner);
                        } else {
                            // Duplicate the entries, but **don't track** the duplications, so the edit page doesn’t think
                            // its entries have been replaced by the other sites’ entries
                            $this->_duplicateEntries($owner, $localizedOwner, trackDuplications: false, force: true);
                        }

                        // Make sure we don't duplicate entries for any of the sites that were just propagated to
                        $handledSiteIds = array_merge($handledSiteIds, array_flip($sourceSupportedSiteIds));
                    }

                    if ($value instanceof EntryQuery) {
                        $owner->setFieldValue($this->handle, $value);
                    }
                }
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Tell the browser to collapse any new entry IDs
        if (
            !Craft::$app->getRequest()->getIsConsoleRequest() &&
            !Craft::$app->getResponse()->isSent &&
            !empty($collapsedEntryIds)
        ) {
            Craft::$app->getSession()->addAssetBundleFlash(MatrixAsset::class);

            foreach ($collapsedEntryIds as $entryId) {
                Craft::$app->getSession()->addJsFlash('Craft.MatrixInput.rememberCollapsedEntryId(' . $entryId . ');', View::POS_END);
            }
        }
    }

    /**
     * Deletes enttries from an owner element
     *
     * @param ElementInterface $owner The owner element
     * @param int[] $except Entry IDs that should be left alone
     */
    private function _deleteOtherEntries(ElementInterface $owner, array $except): void
    {
        /** @var Entry[] $entries */
        $entries = Entry::find()
            ->ownerId($owner->id)
            ->fieldId($this->id)
            ->status(null)
            ->siteId($owner->siteId)
            ->andWhere(['not', ['elements.id' => $except]])
            ->all();

        $elementsService = Craft::$app->getElements();
        $deleteOwnership = [];

        foreach ($entries as $entry) {
            if ($entry->primaryOwnerId === $owner->id) {
                $elementsService->deleteElement($entry);
            } else {
                // Just delete the ownership relation
                $deleteOwnership[] = $entry->id;
            }
        }

        if ($deleteOwnership) {
            Db::delete(Table::ENTRIES_OWNERS, [
                'entryId' => $deleteOwnership,
                'ownerId' => $owner->id,
            ]);
        }
    }

    /**
     * Duplicates entries from one owner element to another.
     *
     * @param ElementInterface $source The source element that entries should be duplicated from
     * @param ElementInterface $target The target element that entries should be duplicated to
     * @param bool $checkOtherSites Whether to duplicate entries for the source element’s other supported sites
     * @param bool $deleteOtherEntries Whether to delete any entries that belong to the element, which weren’t included in the duplication
     * @param bool $trackDuplications whether to keep track of the duplications from [[\craft\services\Elements::$duplicatedElementIds]]
     * and [[\craft\services\Elements::$duplicatedElementSourceIds]]
     * @param bool $force Whether to force duplication, even if it looks like only the entry ownership was duplicated
     * @throws Throwable if reasons
     */
    private function _duplicateEntries(
        ElementInterface $source,
        ElementInterface $target,
        bool $checkOtherSites = false,
        bool $deleteOtherEntries = true,
        bool $trackDuplications = true,
        bool $force = false,
    ): void {
        $elementsService = Craft::$app->getElements();
        /** @var EntryQuery|Collection $value */
        $value = $source->getFieldValue($this->handle);
        if ($value instanceof Collection) {
            $entries = $value->all();
        } else {
            $entries = $value->getCachedResult() ?? (clone $value)->status(null)->all();
        }

        $newEntryIds = [];

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            /** @var Entry[] $entries */
            foreach ($entries as $entry) {
                $newAttributes = [
                    // Only set the canonicalId if the target owner element is a derivative
                    'canonicalId' => $target->getIsDerivative() ? $entry->id : null,
                    'primaryOwnerId' => $target->id,
                    'owner' => $target,
                    'siteId' => $target->siteId,
                    'propagating' => false,
                ];

                if ($target->updatingFromDerivative && $entry->getIsDerivative()) {
                    if (
                        ElementHelper::isRevision($source) ||
                        !empty($target->newSiteIds) ||
                        (!$source::trackChanges() || $source->isFieldModified($this->handle, true))
                    ) {
                        $newEntryId = $elementsService->updateCanonicalElement($entry, $newAttributes)->id;
                    } else {
                        $newEntryId = $entry->getCanonicalId();
                    }
                } elseif (!$force && $entry->primaryOwnerId === $target->id) {
                    // Only the entry ownership was duplicated, so just update its sort order for the target element
                    // (use upsert in case the row doesn’t exist though)
                    Db::upsert(Table::ENTRIES_OWNERS, [
                        'entryId' => $entry->id,
                        'ownerId' => $target->id,
                        'sortOrder' => $entry->sortOrder,
                    ], [
                        'sortOrder' => $entry->sortOrder,
                    ], updateTimestamp: false);
                    $newEntryId = $entry->id;
                } else {
                    $newEntryId = $elementsService->duplicateElement($entry, $newAttributes, trackDuplication: $trackDuplications)->id;
                }

                $newEntryIds[] = $newEntryId;
            }

            if ($deleteOtherEntries) {
                // Delete any entries that shouldn't be there anymore
                $this->_deleteOtherEntries($target, $newEntryIds);
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Duplicate entries for other sites as well?
        if ($checkOtherSites && $this->propagationMethod !== Matrix::PROPAGATION_METHOD_ALL) {
            // Find the target's site IDs that *aren't* supported by this site's entries
            $targetSiteIds = ArrayHelper::getColumn(ElementHelper::supportedSitesForElement($target), 'siteId');
            $fieldSiteIds = self::supportedSiteIds($this->propagationMethod, $target, $this->propagationKeyFormat);
            $otherSiteIds = array_diff($targetSiteIds, $fieldSiteIds);

            if (!empty($otherSiteIds)) {
                // Get the original element and duplicated element for each of those sites
                $otherSources = $target::find()
                    ->drafts($source->getIsDraft())
                    ->provisionalDrafts($source->isProvisionalDraft)
                    ->revisions($source->getIsRevision())
                    ->id($source->id)
                    ->siteId($otherSiteIds)
                    ->status(null)
                    ->all();
                $otherTargets = $target::find()
                    ->drafts($target->getIsDraft())
                    ->provisionalDrafts($target->isProvisionalDraft)
                    ->revisions($target->getIsRevision())
                    ->id($target->id)
                    ->siteId($otherSiteIds)
                    ->status(null)
                    ->indexBy('siteId')
                    ->all();

                // Duplicate entries, ensuring we don't process the same entries more than once
                $handledSiteIds = [];

                foreach ($otherSources as $otherSource) {
                    // Make sure the target actually exists for this site
                    if (!isset($otherTargets[$otherSource->siteId])) {
                        continue;
                    }

                    // Make sure we haven't already duplicated entries for this site, via propagation from another site
                    if (in_array($otherSource->siteId, $handledSiteIds, false)) {
                        continue;
                    }

                    $otherTargets[$otherSource->siteId]->updatingFromDerivative = $target->updatingFromDerivative;
                    $this->_duplicateEntries($otherSource, $otherTargets[$otherSource->siteId]);

                    // Make sure we don't duplicate entries for any of the sites that were just propagated to
                    $sourceSupportedSiteIds = self::supportedSiteIds($this->propagationMethod, $otherSource, $this->propagationKeyFormat);
                    $handledSiteIds = array_merge($handledSiteIds, $sourceSupportedSiteIds);
                }
            }
        }
    }

    /**
     * Duplicates entry ownership relations for a new draft element.
     *
     * @param ElementInterface $canonical The canonical element
     * @param ElementInterface $draft The draft element
     */
    private function _duplicateOwnership(ElementInterface $canonical, ElementInterface $draft): void
    {
        if (!$canonical->getIsCanonical()) {
            throw new InvalidArgumentException('The source element must be canonical.');
        }

        if (!$draft->getIsDraft()) {
            throw new InvalidArgumentException('The target element must be a draft.');
        }

        Craft::$app->getDb()->createCommand(sprintf(
            <<<SQL
INSERT INTO %s ([[entryId]], [[ownerId]], [[sortOrder]]) 
SELECT [[o.entryId]], :draftId, [[o.sortOrder]] 
FROM %s AS [[o]]
INNER JOIN %s AS [[b]] ON [[b.id]] = [[o.entryId]] AND [[b.primaryOwnerId]] = :canonicalId AND [[b.fieldId]] = :fieldId
WHERE [[o.ownerId]] = :canonicalId
SQL,
            Table::ENTRIES_OWNERS,
            Table::ENTRIES_OWNERS,
            Table::ENTRIES,
        ), [
            ':draftId' => $draft->id,
            ':canonicalId' => $canonical->id,
            ':fieldId' => $this->id,
        ])->execute();
    }

    /**
     * Creates revisions for all the entries that belong to the given canonical element, and assigns those
     * revisions to the given owner revision.
     *
     * @param ElementInterface $canonical The canonical element
     * @param ElementInterface $revision The revision element
     */
    private function _createRevisionEntries(ElementInterface $canonical, ElementInterface $revision): void
    {
        // Only fetch entries in the sites the owner element supports
        $siteIds = ArrayHelper::getColumn(ElementHelper::supportedSitesForElement($canonical), 'siteId');

        /** @var Entry[] $entries */
        $entries = Entry::find()
            ->ownerId($canonical->id)
            ->fieldId($this->id)
            ->siteId($siteIds)
            ->preferSites([$canonical->siteId])
            ->unique()
            ->status(null)
            ->all();

        $revisionsService = Craft::$app->getRevisions();
        $ownershipData = [];

        foreach ($entries as $entry) {
            $entryRevisionId = $revisionsService->createRevision($entry, null, null, [
                'primaryOwnerId' => $revision->id,
                'saveOwnership' => false,
            ]);
            $ownershipData[] = [$entryRevisionId, $revision->id, $entry->sortOrder];
        }

        Db::batchInsert(Table::ENTRIES_OWNERS, ['entryId', 'ownerId', 'sortOrder'], $ownershipData);
    }

    /**
     * Merges recent canonical entry changes into the field’s entries.
     *
     * @param ElementInterface $owner The element the field is associated with
     */
    private function _mergeCanonicalChanges(ElementInterface $owner): void
    {
        // Get the owner across all sites
        $localizedOwners = $owner::find()
            ->id($owner->id ?: false)
            ->siteId(['not', $owner->siteId])
            ->drafts($owner->getIsDraft())
            ->provisionalDrafts($owner->isProvisionalDraft)
            ->revisions($owner->getIsRevision())
            ->status(null)
            ->ignorePlaceholders()
            ->indexBy('siteId')
            ->all();
        $localizedOwners[$owner->siteId] = $owner;

        // Get the canonical owner across all sites
        $canonicalOwners = $owner::find()
            ->id($owner->getCanonicalId())
            ->siteId(array_keys($localizedOwners))
            ->status(null)
            ->ignorePlaceholders()
            ->all();

        $elementsService = Craft::$app->getElements();
        $handledSiteIds = [];

        foreach ($canonicalOwners as $canonicalOwner) {
            if (isset($handledSiteIds[$canonicalOwner->siteId])) {
                continue;
            }

            // Get all the canonical owner’s entries, including soft-deleted ones
            /** @var Entry[] $canonicalEntries */
            $canonicalEntries = Entry::find()
                ->fieldId($this->id)
                ->primaryOwnerId($canonicalOwner->id)
                ->siteId($canonicalOwner->siteId)
                ->status(null)
                ->trashed(null)
                ->ignorePlaceholders()
                ->all();

            // Get all the derivative owner’s entries, so we can compare
            /** @var Entry[] $derivativeEntries */
            $derivativeEntries = Entry::find()
                ->fieldId($this->id)
                ->primaryOwnerId($owner->id)
                ->siteId($canonicalOwner->siteId)
                ->status(null)
                ->trashed(null)
                ->ignorePlaceholders()
                ->indexBy('canonicalId')
                ->all();

            foreach ($canonicalEntries as $canonicalEntry) {
                if (isset($derivativeEntries[$canonicalEntry->id])) {
                    $derivativeEntry = $derivativeEntries[$canonicalEntry->id];

                    // Has it been soft-deleted?
                    if ($canonicalEntry->trashed) {
                        // Delete the derivative entry too, unless any changes were made to it
                        if ($derivativeEntry->dateUpdated == $derivativeEntry->dateCreated) {
                            $elementsService->deleteElement($derivativeEntry);
                        }
                    } elseif (!$derivativeEntry->trashed && ElementHelper::isOutdated($derivativeEntry)) {
                        // Merge the upstream changes into the derivative entry
                        $elementsService->mergeCanonicalChanges($derivativeEntry);
                    }
                } elseif (!$canonicalEntry->trashed && $canonicalEntry->dateCreated > $owner->dateCreated) {
                    // This is a new entry, so duplicate it into the derivative owner
                    $elementsService->duplicateElement($canonicalEntry, [
                        'canonicalId' => $canonicalEntry->id,
                        'primaryOwnerId' => $owner->id,
                        'owner' => $localizedOwners[$canonicalEntry->siteId],
                        'siteId' => $canonicalEntry->siteId,
                        'propagating' => false,
                    ]);
                }
            }

            // Keep track of the sites we've already covered
            $siteIds = self::supportedSiteIds($this->propagationMethod, $canonicalOwner, $this->propagationKeyFormat);
            foreach ($siteIds as $siteId) {
                $handledSiteIds[$siteId] = true;
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
        foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
            $elementsService = Craft::$app->getElements();
            /** @var Entry[] $entries */
            $entries = Entry::find()
                ->primaryOwnerId($element->id)
                ->status(null)
                ->siteId($siteId)
                ->all();

            foreach ($entries as $entry) {
                $entry->deletedWithOwner = true;
                $elementsService->deleteElement($entry, $element->hardDelete);
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterElementRestore(ElementInterface $element): void
    {
        // Also restore any entries for this element
        $elementsService = Craft::$app->getElements();
        foreach (ElementHelper::supportedSitesForElement($element) as $siteInfo) {
            /** @var Entry[] $entries */
            $entries = Entry::find()
                ->primaryOwnerId($element->id)
                ->status(null)
                ->siteId($siteInfo['siteId'])
                ->trashed()
                ->andWhere(['entries.deletedWithOwner' => true])
                ->all();

            foreach ($entries as $entry) {
                $elementsService->restoreElement($entry);
            }
        }

        parent::afterElementRestore($element);
    }

    /**
     * Returns info about each entry type and their field types for the field input.
     *
     * @param ElementInterface|null $element
     * @param EntryType[] $entryTypes
     * @param string $placeholderKey
     * @return array
     */
    private function _getEntryTypeInfoForInput(?ElementInterface $element, array $entryTypes, string $placeholderKey): array
    {
        $entryTypeInfo = [];

        // Set a temporary namespace for these
        // Note: we can't just wrap FieldLayoutForm::render() in a callable passed to namespaceInputs() here,
        // because the form HTML is for JavaScript; not returned by inputHtml().
        $view = Craft::$app->getView();
        $oldNamespace = $view->getNamespace();
        $view->setNamespace($view->namespaceInputName("$this->handle[entries][__ENTRY_{$placeholderKey}__]"));

        foreach ($entryTypes as $entryType) {
            // Create a fake entry so the field types have a way to get at the owner element, if there is one
            $entry = new Entry([
                'fieldId' => $this->id,
                'typeId' => $entryType->id,
            ]);

            if ($element) {
                $entry->setOwner($element);
                $entry->siteId = $element->siteId;
            }

            $fieldLayout = $entryType->getFieldLayout();
            $fields = $fieldLayout->getCustomFields();

            foreach ($fields as $field) {
                $field->setIsFresh(true);
            }

            $view->startJsBuffer();
            $bodyHtml = $view->namespaceInputs($fieldLayout->createForm($entry)->render());
            $js = $view->clearJsBuffer();

            // Reset $_isFresh's
            foreach ($fields as $field) {
                $field->setIsFresh(null);
            }

            $entryTypeInfo[] = [
                'handle' => $entryType->handle,
                'name' => Craft::t('site', $entryType->name),
                'bodyHtml' => $bodyHtml,
                'js' => $js,
            ];
        }

        $view->setNamespace($oldNamespace);
        return $entryTypeInfo;
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

        // Get the old entries
        if ($element->id) {
            /** @var Entry[] $oldEntriesById */
            $oldEntriesById = Entry::find()
                ->fieldId($this->id)
                ->ownerId($element->id)
                ->siteId($element->siteId)
                ->drafts(null)
                ->revisions(null)
                ->status(null)
                ->indexBy('id')
                ->all();
        } else {
            $oldEntriesById = [];
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
                isset(Elements::$duplicatedElementSourceIds[$entryId]) &&
                isset($newEntryData[Elements::$duplicatedElementSourceIds[$entryId]])
            ) {
                // $entryId is a duplicated entry's ID, but the data was sent with the original entry ID
                $entryData = $newEntryData[Elements::$duplicatedElementSourceIds[$entryId]];
            } else {
                $entryData = [];
            }

            // If this is a preexisting entry but we don't have a record of it,
            // check to see if it was recently duplicated.
            if (
                !str_starts_with($entryId, 'new') &&
                !isset($oldEntriesById[$entryId]) &&
                isset(Elements::$duplicatedElementIds[$entryId]) &&
                isset($oldEntriesById[Elements::$duplicatedElementIds[$entryId]])
            ) {
                $entryId = Elements::$duplicatedElementIds[$entryId];
            }

            // Existing entry?
            if (isset($oldEntriesById[$entryId])) {
                /** @var Entry $entry */
                $entry = $oldEntriesById[$entryId];
                $dirty = !empty($entryData);

                // Is this a derivative element, and does the entry primarily belong to the canonical?
                if ($dirty && $element->getIsDerivative() && $entry->primaryOwnerId === $element->getCanonicalId()) {
                    // Duplicate it as a draft. (We'll drop its draft status from _saveEntries().)
                    $entry = Craft::$app->getDrafts()->createDraft($entry, Craft::$app->getUser()->getId(), null, null, [
                        'canonicalId' => $entry->id,
                        'primaryOwnerId' => $element->id,
                        'owner' => $element,
                        'siteId' => $element->siteId,
                        'propagating' => false,
                        'markAsSaved' => false,
                    ]);
                }

                $entry->dirty = $dirty;
            } else {
                // Make sure it's a valid entry type
                if (!isset($entryData['type']) || !isset($entryTypes[$entryData['type']])) {
                    continue;
                }
                $entry = new Entry();
                $entry->fieldId = $this->id;
                $entry->typeId = $entryTypes[$entryData['type']]->id;
                $entry->primaryOwnerId = $entry->ownerId = $element->id;
                $entry->siteId = $element->siteId;

                // Preserve the collapsed state, which the browser can't remember on its own for new entries
                $entry->collapsed = !empty($entryData['collapsed']);
            }

            if (isset($entryData['enabled'])) {
                $entry->enabled = (bool)$entryData['enabled'];
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
                $entry->setFieldParamNamespace("$baseEntryFieldNamespace.$entryId.fields");
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
