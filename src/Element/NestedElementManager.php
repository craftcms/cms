<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use Closure;
use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Concerns\LegacyNestedElementManager;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Data\NestedElementCard;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Element\Events\NestedElementRevisionsCreated;
use CraftCms\Cms\Element\Events\NestedElementsDuplicated;
use CraftCms\Cms\Element\Events\NestedElementsSaved;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\Contracts\NestedElementQueryInterface;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Http\ViewModels\EmbeddedIndexViewModel;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Drafts as DraftsFacade;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Workflows as WorkflowsFacade;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Ui\Controls\NestedElements;
use Generator;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\renderObjectTemplate;
use function CraftCms\Cms\t;

/**
 * This can be used by elements or fields to manage nested elements, such as users -> addresses,
 * or Matrix fields -> nested entries.
 *
 * If this is for a custom field, [[field]] must be set. Otherwise, [[attribute]] must be set.
 *
 * @since 6.0.0
 */
class NestedElementManager extends Component
{
    use LegacyNestedElementManager;

    private const string VIEW_MODE_CARDS = 'cards';

    private const string VIEW_MODE_INDEX = 'index';

    /** @var array<string,string|false> */
    private static array $renderedPropagationFormats = [];

    /**
     * @param  class-string<NestedElementInterface>  $elementType
     * @param  Closure(ElementInterface): ElementQueryInterface  $queryFactory
     * @param  array<string,mixed>  $config
     */
    public function __construct(
        private readonly string $elementType,
        private readonly Closure $queryFactory,
        array $config = [],
    ) {
        parent::__construct($config);

        if (! isset($this->attribute) && ! isset($this->field)) {
            throw new RuntimeException('NestedElementManager requires that either `attribute` or `field` is set.');
        }

        if (isset($this->attribute, $this->field)) {
            throw new RuntimeException('NestedElementManager requires that either `attribute` or `field` is set, but not both.');
        }
    }

    public ?string $attribute = null;

    public ?FieldInterface $field = null;

    public string $ownerIdParam = 'ownerId';

    public string $primaryOwnerIdParam = 'primaryOwnerId';

    /** @var array<string,mixed> */
    public array $criteria = [];

    public ?Closure $valueGetter = null;

    public Closure|null|false $valueSetter = null;

    public PropagationMethod $propagationMethod = PropagationMethod::All;

    public ?string $propagationKeyFormat = null;

    public bool $keepOtherNestedElements = false;

    public function getIsTranslatable(?ElementInterface $owner = null): bool
    {
        if ($this->propagationMethod === PropagationMethod::Custom && $this->propagationKeyFormat !== null) {
            return $owner === null || renderObjectTemplate($this->propagationKeyFormat, $owner) !== '';
        }

        return $this->propagationMethod !== PropagationMethod::All;
    }

    private function nestedElementQuery(ElementInterface $owner): ElementQueryInterface
    {
        return call_user_func($this->queryFactory, $owner);
    }

    /** @return ElementQueryInterface|ElementCollection<array-key,ElementInterface> */
    private function getValue(ElementInterface $owner, bool $fetchAll = false): ElementQueryInterface|ElementCollection
    {
        if (isset($this->valueGetter)) {
            return call_user_func($this->valueGetter, $owner, $fetchAll);
        }

        if (isset($this->attribute)) {
            return $owner->{$this->attribute};
        }

        $query = $owner->getFieldValue($this->field->handle);

        if ($query instanceof ElementCollection) {
            return $query;
        }

        if (! $query instanceof ElementQueryInterface) {
            $query = $this->nestedElementQuery($owner);
        }

        $result = $query->getResultOverride();

        if ($fetchAll && $result === null) {
            $query
                ->drafts(null)
                ->canonicalsOnly()
                ->savedDraftsOnly()
                ->status(null)
                ->limit(null);
        }

        return $query;
    }

    /** @param ElementQueryInterface|ElementCollection<array-key,ElementInterface> $value */
    private function setValue(ElementInterface $owner, ElementQueryInterface|ElementCollection $value): void
    {
        if ($this->valueSetter === false) {
            return;
        }

        if (isset($this->valueSetter)) {
            call_user_func($this->valueSetter, $value, $owner);
        } elseif (isset($this->attribute)) {
            $owner->{$this->attribute} = $value;
        } else {
            $owner->setFieldValue($this->field->handle, $value);
        }
    }

    /**
     * @param  NestedElementInterface[]  $elements
     */
    private function setOwnerOnNestedElements(ElementInterface $owner, array $elements): void
    {
        foreach ($elements as $element) {
            $element->setOwner($owner);

            if ($owner->id === $element->getPrimaryOwnerId()) {
                $element->setPrimaryOwner($owner);
            }
        }
    }

    public function getSearchKeywords(ElementInterface $owner): string
    {
        $keywords = [];
        /** @var NestedElementInterface[] $elements */
        $elements = $this->getValue($owner)->all();
        $this->setOwnerOnNestedElements($owner, $elements);

        foreach ($elements as $element) {
            $hasTitles ??= $element::hasTitles();
            if ($hasTitles) {
                $keywords[] = $element->title;
            }

            foreach ($element->getFieldLayout()->getCustomFields() as $field) {
                if ($field->searchable) {
                    $fieldValue = $element->getFieldValue($field->handle);
                    $keywords[] = $field->getSearchKeywords($fieldValue, $element);
                }
            }
        }

        return Str::toString($keywords, ' ');
    }

    public function getTranslationDescription(?ElementInterface $owner = null): ?string
    {
        if (! $owner) {
            return null;
        }

        return match ($this->propagationMethod) {
            PropagationMethod::None => t('{type} will only be saved in the {site} site.', [
                'type' => $this->elementType::pluralDisplayName(),
                'site' => t($owner->getSite()->getName(), category: 'site'),
            ]),
            PropagationMethod::SiteGroup => t('{type} will be saved across all sites in the {group} site group.', [
                'type' => $this->elementType::pluralDisplayName(),
                'group' => t($owner->getSite()->getGroup()->getName(), category: 'site'),
            ]),
            PropagationMethod::Language => t('{type} will be saved across all {language}-language sites.', [
                'type' => $this->elementType::pluralDisplayName(),
                'language' => I18N::getLocaleById($owner->getSite()->getLanguage())->getDisplayName(app()->getLocale()),
            ]),
            default => null,
        };
    }

    /**
     * @return int[]
     */
    public function getSupportedSiteIds(ElementInterface $owner): array
    {
        /** @var Site[] $allSites */
        $allSites = Sites::getAllSites()->keyBy('id')->all();
        $ownerSiteIds = array_map(
            fn (array $siteInfo) => $siteInfo['siteId'],
            ElementHelper::supportedSitesForElement($owner),
        );
        $siteIds = [];

        if ($this->propagationMethod === PropagationMethod::Custom && $this->propagationKeyFormat !== null) {
            $cacheKey = sprintf('%s-%s-%s', md5($this->propagationKeyFormat), $owner->id, $owner->siteId);
            self::$renderedPropagationFormats[$cacheKey] ??= renderObjectTemplate($this->propagationKeyFormat, $owner);
            $propagationKey = self::$renderedPropagationFormats[$cacheKey];
        }

        foreach ($ownerSiteIds as $siteId) {
            $include = match ($this->propagationMethod) {
                PropagationMethod::None => $siteId === $owner->siteId,
                PropagationMethod::SiteGroup => $allSites[$siteId]->groupId === $allSites[$owner->siteId]->groupId,
                PropagationMethod::Language => $allSites[$siteId]->getLanguage() === $allSites[$owner->siteId]->getLanguage(),
                PropagationMethod::Custom => $this->isCustomPropagationMatch($owner, $siteId, $propagationKey ?? null),
                default => true,
            };

            if ($include) {
                $siteIds[] = $siteId;
            }
        }

        return $siteIds;
    }

    private function isCustomPropagationMatch(ElementInterface $owner, int $siteId, ?string $propagationKey): bool
    {
        if (! isset($propagationKey)) {
            return true;
        }

        $cacheKey = sprintf('%s-%s-%s', md5((string) $this->propagationKeyFormat), $owner->id, $siteId);
        if (! isset(self::$renderedPropagationFormats[$cacheKey])) {
            $siteOwner = Elements::getElementById($owner->id, $owner::class, $siteId);
            self::$renderedPropagationFormats[$cacheKey] = $siteOwner
                ? renderObjectTemplate((string) $this->propagationKeyFormat, $siteOwner)
                : false;
        }

        return $propagationKey === self::$renderedPropagationFormats[$cacheKey];
    }

    /**
     * Returns the settings/data payload for a card grid of nested elements,
     * including per-element card data in the shape the Vue cards consume
     * (`id`, `cardAttributes`, and the
     * `cardHeaderHtml`/`cardContentHtml`/`cardFooterHtml` parts) — so a
     * front-end (e.g. a Vue page) can render the cards itself instead of
     * consuming server-rendered markup.
     *
     * Returns `null` when the owner hasn't been saved yet.
     *
     * Grants the session authorization the nested-element endpoints require,
     * when the control is editable. Namespace-derived values (`baseInputName`)
     * reflect the calling namespace context.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>|null
     */
    public function getCardsData(?ElementInterface $owner, array $config = []): ?array
    {
        if (! $owner?->id) {
            return null;
        }

        $config = $this->normalizeViewConfig($this->normalizeCardsConfig($config));
        $attribute = $this->viewAttribute();
        if (! $config['static']) {
            $this->authorizeNestedElementManagement($owner, $attribute);
            if ($config['sortable']) {
                $this->authorizeNestedElementReordering($owner, $attribute);
            }
        }

        $settings = $this->viewSettings($owner, $config, self::VIEW_MODE_CARDS, $attribute)
            + $this->cardsSettings($config);

        $settings['elementType'] = $this->elementType;

        $elementHtml = app(ElementHtml::class);
        $settings['elements'] = array_map(function (ElementInterface $element) use ($elementHtml, $config, $owner): NestedElementCard {
            // A per-element `id` is shared across the card parts so they line
            // up when recomposed client-side, while staying unique per card.
            $cardConfig = $this->cardConfig($config, $element) + [
                'id' => sprintf('card-%s', mt_rand()),
                'canPaste' => $config['canPaste'],
                'nestedActionEvents' => $config['nestedActionEvents'],
            ];
            $cardConfig['showNestedActions'] = $config['static'] ? false : [
                'ownerElementType' => $owner::class,
                'ownerId' => $owner->id,
                'ownerSiteId' => $owner->siteId,
                'attribute' => $this->viewAttribute(),
            ];
            $editUrl = self::elementEditUrl(
                $element,
                $this->field?->id,
                $element->getOwnerId(),
                $config['prevalidate'],
            );
            $cardData = $elementHtml->elementCardData($element, $cardConfig);

            return new NestedElementCard(
                id: $element->id,
                siteId: $element->siteId,
                entryTypeId: $element instanceof Entry ? $element->typeId : null,
                ownerId: $element->getOwnerId(),
                ownerIsCanonical: $elementHtml->elementOwnerIsCanonical($element),
                isUnpublishedDraft: $element->getIsUnpublishedDraft(),
                ownerIsUnpublishedDraft: $owner->getIsUnpublishedDraft(),
                primaryOwnerId: $element->getPrimaryOwnerId(),
                isCanonical: $element->getIsCanonical(),
                capabilities: $elementHtml->elementCapabilities($element, ElementSources::CONTEXT_EMBEDDED_INDEX),
                editUrl: $editUrl,
                cpEditUrl: $element->getCpEditUrl(),
                actionMenuItems: $elementHtml->elementCardActionItems($element, $cardConfig),
                cardAttributes: $cardData['cardAttributes'],
                cardHeaderHtml: $cardData['cardHeaderHtml'],
                cardActionsHtml: $cardData['cardActionsHtml'],
                cardContentHtml: $cardData['cardContentHtml'],
                cardFooterHtml: $cardData['cardFooterHtml'],
                cardThumbHtml: $cardData['cardThumbHtml'],
                thumbAlignment: $cardData['thumbAlignment'],
            );
        }, $this->cardElements($owner));

        return $settings;
    }

    /**
     * Returns the nested element’s owner-scoped slideout URL.
     */
    public static function elementEditUrl(
        NestedElementInterface $element,
        ?int $fieldId = null,
        ?int $ownerId = null,
        bool $prevalidate = false,
    ): string {
        // Nested elements are edited in an `elements/edit` slideout, whether or not they also have an edit page.
        return Url::cpUrl(Cms::config()->actionTrigger.'/elements/edit', array_filter([
            'elementId' => $element->isProvisionalDraft ? $element->getCanonicalId() : $element->id,
            'siteId' => $element->siteId,
            'fieldId' => $fieldId,
            'ownerId' => $ownerId ?? $element->getOwnerId(),
            'draftId' => $element->isProvisionalDraft ? null : $element->draftId,
            'revisionId' => $element->revisionId,
            'prevalidate' => $prevalidate ? 1 : null,
        ], fn (mixed $value): bool => $value !== null));
    }

    /**
     * Applies the cards-view config defaults.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function normalizeCardsConfig(array $config): array
    {
        return $config + [
            'showInGrid' => false,
            'prevalidate' => false,
            'selectable' => false,
        ];
    }

    /**
     * The cards-mode additions to the manager settings payload.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function cardsSettings(array $config): array
    {
        return [
            'deleteLabel' => mb_ucfirst(t('Delete {type}', [
                'type' => $this->elementType::lowerDisplayName(),
            ])),
            'deleteConfirmationMessage' => t('Are you sure you want to delete the selected {type}?', [
                'type' => $this->elementType::lowerDisplayName(),
            ]),
            'bulkDeleteConfirmationMessage' => t('Are you sure you want to delete the selected {type}?', [
                'type' => $this->elementType::pluralLowerDisplayName(),
            ]),
            'showInGrid' => $config['showInGrid'],
            'selectable' => $config['selectable'],
        ];
    }

    /**
     * The per-card render config for the nested context.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function cardConfig(array $config, ElementInterface $element): array
    {
        return [
            'context' => 'field',
            'showActionMenu' => true,
            'showStatus' => true,
            'showNestedActions' => ! $config['static'],
            'selectable' => $config['selectable'],
            'sortable' => $config['sortable'],
            'showInGrid' => $config['showInGrid'] ?? false,
            'attributes' => [
                'data' => array_filter([
                    'entry-type-id' => $element instanceof Entry ? $element->typeId : null,
                ]),
            ],
        ];
    }

    /**
     * Fetches the owner's nested elements ready for card rendering:
     * provisional changes loaded, validated when the owner has errors, and
     * with their owner set.
     *
     * @return NestedElementInterface[]
     */
    private function cardElements(ElementInterface $owner): array
    {
        $value = $this->getValue($owner, true);
        if ($value instanceof ElementCollection) {
            /** @var NestedElementInterface[] $elements */
            $elements = $value->all();
        } else {
            /** @var NestedElementInterface[] $elements */
            $elements = $value->getResultOverride() ?? $value
                ->status(null)
                ->limit(null)
                ->all();
        }

        app(Drafts::class)->loadProvisionalChanges($elements);

        if ($this->hasErrors($owner)) {
            foreach ($elements as $element) {
                if ($element->enabled && $element->getEnabledForSite()) {
                    $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
                }
                $element->validate();
            }
        }

        $this->setOwnerOnNestedElements($owner, $elements);

        return $elements;
    }

    /**
     * Returns the settings/data payload for a client-rendered embedded index
     * of the nested elements.
     *
     * Returns `null` when the owner hasn't been saved yet.
     *
     * Grants the session authorization the nested-element endpoints require
     * when the control is editable. Legacy-only index settings are omitted.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>|null
     */
    public function getIndexData(?ElementInterface $owner, array $config = []): ?array
    {
        if (! $owner?->id) {
            return null;
        }

        $config = $this->getIndexConfig($owner, $config);
        $attribute = $this->viewAttribute();
        if (! $config['static']) {
            $this->authorizeNestedElementManagement($owner, $attribute);
        }

        $settings = $this->viewSettings($owner, $config, self::VIEW_MODE_INDEX, $attribute);
        $settings['elementType'] = $this->elementType;
        $settings['indexSettings'] = [
            'showHeaderColumn' => $config['showHeaderColumn'],
            'storageKey' => $config['storageKey'],
            'static' => $config['static'],
        ];
        unset($settings['ownerIdParam']);

        if (! $config['static'] && $config['sortable']) {
            $this->authorizeNestedElementReordering($owner, $attribute);
        }

        return $settings;
    }

    /**
     * Resolves the complete server-owned configuration for an embedded index.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function getIndexConfig(?ElementInterface $owner, array $config = []): array
    {
        return $this->normalizeViewConfig($this->normalizeIndexConfig($owner, $config));
    }

    /**
     * Builds the UI control that manages these nested elements outside the owner's form,
     * as cards or an embedded element index.
     *
     * This is what fields and owner screens hand the Vue editor, so every nested element type
     * (entries, addresses, or a plugin's own) gets the same create, edit, reorder, paste, and
     * delete behavior.
     *
     * @param  string|list<string>  $path  The control path within the owner's UI
     * @param  'cards'|'cards-grid'|'index'  $viewMode
     * @param  array<string, mixed>  $config  The cards or index view config
     */
    public function uiControl(string|array $path, ?ElementInterface $owner, string $viewMode, array $config = []): NestedElements
    {
        // The Vue cards build their action menus from structured card data.
        $config += ['nestedActionEvents' => true];

        $control = NestedElements::make($path)
            ->viewMode($viewMode)
            ->unavailableMessage($owner?->id ? null : t('{nestedType} can only be created after the {ownerType} has been saved.', [
                'nestedType' => $this->elementType::pluralDisplayName(),
                'ownerType' => $owner ? $owner::lowerDisplayName() : t('element'),
            ]));

        if ($viewMode === self::VIEW_MODE_INDEX) {
            $config = $this->getIndexConfig($owner, $config);
            $data = $this->getIndexData($owner, $config);

            if ($data === null) {
                return $control;
            }

            return $control
                ->manager(Arr::except($data, ['indexSettings']))
                ->index([
                    'indexSettings' => $data['indexSettings'],
                    'initial' => EmbeddedIndexViewModel::forOwner(
                        $this->elementType,
                        $owner,
                        $this->viewAttribute(),
                        $config,
                    )->payload(),
                ]);
        }

        $data = $this->getCardsData($owner, $config);

        return $control
            ->manager($data === null ? null : Arr::except($data, ['elements']))
            ->cards($data['elements'] ?? []);
    }

    /**
     * Returns the manager defaults when an embedded attribute has no field-specific manager API.
     *
     * @return array<string, mixed>
     */
    public static function defaultIndexConfig(
        ?ElementInterface $owner,
        ?FieldInterface $field = null,
        ?string $attribute = null,
    ): array {
        $storageKey = null;

        if ($field !== null) {
            if ($field::isMultiInstance()) {
                if (isset($field->layoutElement)) {
                    $storageKey = sprintf('field:%s', $field->layoutElement->uid);
                }
            } else {
                $storageKey = sprintf('field:%s', $field->uid);
            }
        } elseif ($owner !== null && $attribute !== null) {
            $storageKey = sprintf('%s:%s', $owner::class, $attribute);
        }

        return [
            'allowedViewModes' => null,
            'showHeaderColumn' => true,
            'fieldLayouts' => [],
            'defaultSort' => null,
            'defaultTableColumns' => null,
            'prevalidate' => false,
            'pageSize' => 50,
            'storageKey' => $storageKey,
            'defaultViewMode' => 'cards',
            'static' => $owner?->getIsRevision() ?? false,
            'sortable' => false,
            'canPaste' => false,
            'fieldId' => $field?->id,
        ];
    }

    /**
     * Applies the index-view config defaults.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function normalizeIndexConfig(?ElementInterface $owner, array $config): array
    {
        return $config + self::defaultIndexConfig($owner, $this->field, $this->attribute);
    }

    /**
     * Applies the shared view config defaults (create/paste/limit options)
     * used by cards and indexes.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function normalizeViewConfig(array $config): array
    {
        $config += [
            'static' => false,
            'nestedActionEvents' => false,
            'sortable' => false,
            'canCreate' => false,
            'canPaste' => false,
            'pasteableData' => null,
            'createButtonLabel' => null,
            'createAttributes' => [],
            'minElements' => null,
            'maxElements' => null,
        ];

        $config['createButtonLabel'] ??= t('New {type}', [
            'type' => $this->elementType::lowerDisplayName(),
        ]);

        if ($config['createAttributes'] === [] && $config['canCreate']) {
            $config['createAttributes'] = [[
                'label' => $config['createButtonLabel'],
                'attributes' => [],
            ]];
        } elseif (! empty($config['createAttributes']) && ! array_is_list($config['createAttributes'])) {
            $config['createAttributes'] = [[
                'label' => $config['createButtonLabel'],
                'attributes' => $config['createAttributes'],
            ]];
        }

        return $config;
    }

    /**
     * The owner attribute (or `field:<handle>`) the nested elements belong to.
     */
    private function viewAttribute(): string
    {
        return $this->attribute ?? sprintf('field:%s', $this->field->handle);
    }

    /**
     * Grants the session authorization the nested-element endpoints require
     * for this owner/attribute.
     */
    private function authorizeNestedElementManagement(ElementInterface $owner, string $attribute): void
    {
        SessionAuth::authorize(sprintf('manageNestedElements::%s::%s', $this->authorizedOwnerId($owner), $attribute));
    }

    /**
     * Grants the session authorization the nested-element reorder endpoint requires
     * for this owner/attribute. Only relevant when the field is sortable, since
     * {@see authorizeNestedElementManagement()}'s authorization is not sufficient
     * to allow reordering on its own.
     */
    private function authorizeNestedElementReordering(ElementInterface $owner, string $attribute): void
    {
        SessionAuth::authorize(sprintf('reorderNestedElements::%s::%s', $this->authorizedOwnerId($owner), $attribute));
    }

    private function authorizedOwnerId(ElementInterface $owner): int
    {
        if ($owner->isProvisionalDraft && $owner->draftCreatorId === currentUser()?->getCraftUserId()) {
            /** @var ElementInterface $owner */
            return $owner->getCanonicalId();
        }

        return $owner->id;
    }

    /**
     * Builds the manager settings payload shared by cards and indexes.
     *
     * @param array{
     *     sortable: bool,
     *     canCreate: mixed,
     *     canPaste: mixed,
     *     pasteableData: mixed,
     *     minElements: mixed,
     *     maxElements: mixed,
     *     createButtonLabel: mixed,
     *     prevalidate?: mixed,
     *     createAttributes?: list<array{label: string, attributes: array<string, mixed>, icon?: mixed, color?: mixed, group?: mixed}>
     * } $config
     * @return array<string, mixed>
     */
    private function viewSettings(ElementInterface $owner, array $config, string $mode, string $attribute): array
    {
        $settings = [
            'mode' => $mode,
            'ownerElementType' => $owner::class,
            'ownerId' => $owner->id,
            'ownerSiteId' => $owner->siteId,
            'ownerIsDerivative' => $owner->getIsDerivative(),
            'ownerIsInDerivativeTree' => ElementHelper::isDraftOrRevision($owner),
            'ownerIsUnpublishedDraft' => $owner->getIsUnpublishedDraft(),
            'ownerHasDrafts' => $owner->getRootOwner()::hasDrafts(),
            'attribute' => $attribute,
            'sortable' => $config['sortable'],
            'canCreate' => $config['canCreate'],
            'canPaste' => $config['canPaste'],
            'pasteableData' => $config['pasteableData'],
            'createAttributes' => $config['createAttributes'],
            'minElements' => $config['minElements'],
            'maxElements' => $config['maxElements'],
            'createButtonLabel' => $config['createButtonLabel'],
            'ownerIdParam' => $this->ownerIdParam,
            'fieldId' => $this->field?->id,
            'fieldHandle' => $this->field?->handle,
            'baseInputName' => InputNamespace::get(),
            'prevalidate' => $config['prevalidate'] ?? false,
        ];

        $settings['createAttributes'] = array_map(function (array $attributes): array {
            if (isset($attributes['color']) && $attributes['color'] instanceof Color) {
                $attributes['color'] = $attributes['color']->value;
            }

            return $attributes;
        }, $settings['createAttributes']);

        return $settings;
    }

    public function maintainNestedElements(ElementInterface $owner, bool $isNew): void
    {
        $resetValue = false;

        if ($owner->duplicateOf !== null) {
            if ($owner->getIsRevision()) {
                $this->createRevisions($owner->duplicateOf, $owner);
                // getIsUnpublishedDraft is needed for "save as new" duplication
            } elseif (! $owner->getIsDraft() || $owner->getIsUnpublishedDraft()) {
                $this->duplicateNestedElements($owner->duplicateOf, $owner, true, ! $isNew);
            }
            $resetValue = true;
        } elseif (
            $this->isDirty($owner) ||
            $this->propagateRequired($owner) ||
            ! empty($owner->newSiteIds)
        ) {
            $this->saveNestedElements($owner);
        } elseif ($owner->mergingCanonicalChanges) {
            $this->mergeCanonicalChanges($owner);
            $resetValue = true;
        }

        if ($isNew || $resetValue) {
            $dirtyFields = $owner->getDirtyFields();
            $this->setValue($owner, $this->nestedElementQuery($owner));
            $owner->setDirtyFields($dirtyFields, false);
        }
    }

    private function isDirty(ElementInterface $owner): bool
    {
        if (isset($this->attribute)) {
            return $owner->isAttributeDirty($this->attribute);
        }

        foreach ($this->fieldInstances($owner) as $instance) {
            /** @var FieldInterface $instance */
            if ($owner->isFieldDirty($instance->handle)) {
                return true;
            }
        }

        return false;
    }

    private function isModified(ElementInterface $owner, bool $anySite = false): bool
    {
        if (isset($this->attribute)) {
            return $owner->isAttributeModified($this->attribute);
        }

        foreach ($this->fieldInstances($owner) as $instance) {
            /** @var FieldInterface $instance */
            if ($owner->isFieldModified($instance->handle, $anySite)) {
                return true;
            }
        }

        return false;
    }

    private function hasErrors(ElementInterface $owner): bool
    {
        if (isset($this->attribute)) {
            return $owner->errors()->has($this->attribute) || $owner->errors()->has("$this->attribute.*");
        }

        foreach ($this->fieldInstances($owner) as $instance) {
            /** @var FieldInterface $instance */
            if ($owner->errors()->has($instance->handle) || $owner->errors()->has("$instance->handle.*")) {
                return true;
            }
        }

        return false;
    }

    private function fieldInstances(ElementInterface $owner): Generator
    {
        if (! isset($this->field)) {
            return;
        }

        if (! $this->field::isMultiInstance()) {
            yield $this->field;

            return;
        }

        $customFields = $owner->getFieldLayout()?->getCustomFields() ?? [];
        foreach ($customFields as $field) {
            if ($field->id === $this->field->id) {
                yield $field;
            }
        }
    }

    private function propagateRequired(ElementInterface $owner, ?ElementInterface $localizedOwner = null): bool
    {
        foreach ($this->fieldInstances($owner) as $instance) {
            if (
                $instance->layoutElement->required &&
                (
                    ! $localizedOwner ||
                    $instance->isValueEmpty($localizedOwner->getFieldValue($instance->handle), $localizedOwner)
                )
            ) {
                return true;
            }
        }

        return false;
    }

    private function saveNestedElements(ElementInterface $owner): void
    {
        $value = $this->getValue($owner, true);
        if ($value instanceof ElementCollection) {
            $elements = $value->all();
            $saveAll = true;
        } else {
            $elements = $value->getResultOverride();
            if ($elements !== null) {
                $saveAll = ! empty($owner->newSiteIds);
            } else {
                $elements = $value->all();
                $saveAll = true;
            }
        }

        /** @var NestedElementInterface[] $elements */
        $this->setOwnerOnNestedElements($owner, $elements);

        $elementIds = [];
        $sortOrder = 0;

        DB::beginTransaction();
        try {
            /** @var NestedElementInterface[] $elements */
            foreach ($elements as $element) {
                if (isset($element->dateDeleted)) {
                    Elements::restoreElement($element);
                }

                if ($owner->propagateRequired) {
                    $element->propagateRequired = true;
                }

                $sortOrder++;
                if ($saveAll || ! $element->id || $element->forceSave) {
                    $element->setOwner($owner);
                    $element->setSortOrder($sortOrder);
                    $element->resaving = $owner->resaving && $element->id;
                    // $owner is already being saved, so it (and its own ancestors, if any) will get its
                    // `dateUpdated` timestamp updated on its own; no need to do that here as well.
                    // see https://github.com/craftcms/cms/issues/19594
                    $element->touchOwnersOnSave = false;
                    Elements::saveElement($element, false);
                    $element->touchOwnersOnSave = true;

                    if (
                        $element->getPrimaryOwnerId() === $owner->id &&
                        $element->getIsDraft() &&
                        ! $element->getIsUnpublishedDraft() &&
                        ! $owner->getIsCanonical() &&
                        ! $owner->getIsUnpublishedDraft()
                    ) {
                        /** @var NestedElementInterface $canonical */
                        $canonical = $element->getCanonical(true);
                        if (ElementHelper::belongsToCanonicalOwner($canonical, $owner)) {
                            app(Drafts::class)->removeDraftData($element);
                            DB::table(Table::ELEMENTS_OWNERS)
                                ->where('elementId', $canonical->id)
                                ->where('ownerId', $owner->id)
                                ->delete();
                        }
                    } elseif (
                        $element->getIsUnpublishedDraft() &&
                        $element->getPrimaryOwnerId() === $owner->id
                    ) {
                        app(Drafts::class)->removeDraftData($element);
                    }
                } elseif ((int) $element->getSortOrder() !== $sortOrder) {
                    $element->setSortOrder($sortOrder);
                    DB::table(Table::ELEMENTS_OWNERS)
                        ->where([
                            'elementId' => $element->id,
                            'ownerId' => $owner->id,
                        ])
                        ->update(['sortOrder' => $sortOrder]);
                }

                $elementIds[] = $element->id;
            }

            if (! $this->keepOtherNestedElements) {
                $this->deleteOtherNestedElements($owner, $elementIds);
            }

            if (
                $this->propagationMethod !== PropagationMethod::All &&
                (
                    $owner->propagateAll ||
                    $this->propagateRequired($owner) ||
                    ! empty($owner->newSiteIds)
                )
            ) {
                $ownerSiteIds = array_map(
                    fn (array $siteInfo) => $siteInfo['siteId'],
                    ElementHelper::supportedSitesForElement($owner),
                );
                $fieldSiteIds = $this->getSupportedSiteIds($owner);
                $otherSiteIds = array_diff($ownerSiteIds, $fieldSiteIds);

                if (! $owner->propagateAll && ! $this->propagateRequired($owner)) {
                    $preexistingOtherSiteIds = array_diff($otherSiteIds, $owner->newSiteIds);
                    $otherSiteIds = array_intersect($otherSiteIds, $owner->newSiteIds);
                } else {
                    $preexistingOtherSiteIds = [];
                }

                if (! empty($otherSiteIds)) {
                    $ownerQuery = $owner::find()
                        ->drafts($owner->getIsDraft())
                        ->provisionalDrafts($owner->isProvisionalDraft)
                        ->revisions($owner->getIsRevision())
                        ->id($owner->id)
                        ->status(null);

                    // If the owner is nested too, retain its own owner, so it doesn't fall back to its primary owner
                    // (e.g. the canonical element when it's shared with a draft), which may not support the other sites
                    // (see https://github.com/craftcms/cms/issues/18281)
                    if (
                        $owner instanceof NestedElementInterface &&
                        $ownerQuery instanceof NestedElementQueryInterface &&
                        $ownerId = $owner->getOwnerId()
                    ) {
                        $ownerQuery->ownerId($ownerId);
                    }

                    $localizedOwners = (clone $ownerQuery)
                        ->siteId($otherSiteIds)
                        ->all();

                    $handledSiteIds = [];

                    if ($value instanceof ElementQueryInterface) {
                        $cachedQuery = (clone $value)->status(null);
                        $cachedQuery->setResultOverride($elements);
                        $this->setValue($owner, $cachedQuery);
                    }

                    foreach ($localizedOwners as $localizedOwner) {
                        if (isset($handledSiteIds[$localizedOwner->siteId])) {
                            continue;
                        }

                        $sourceSupportedSiteIds = $this->getSupportedSiteIds($localizedOwner);

                        if (
                            ! empty($preexistingOtherSiteIds) &&
                            ! empty($sharedPreexistingOtherSiteIds = array_intersect($preexistingOtherSiteIds, $sourceSupportedSiteIds)) &&
                            $preexistingLocalizedOwner = (clone $ownerQuery)
                                ->siteId($sharedPreexistingOtherSiteIds)
                                ->one()
                        ) {
                            $this->saveNestedElements($preexistingLocalizedOwner);
                        } else {
                            // Duplicate the elements, but **don't track** the duplications, so the edit page doesn’t think
                            // its elements have been replaced by the other sites’ nested elements
                            if ($owner->propagateAll || $this->propagateRequired($owner, $localizedOwner) || in_array($localizedOwner->siteId, $owner->newSiteIds)) {
                                $this->duplicateNestedElements($owner, $localizedOwner, force: true);
                            }
                        }

                        foreach ($sourceSupportedSiteIds as $siteId) {
                            $handledSiteIds[$siteId] = true;
                        }
                    }

                    if ($value instanceof ElementQueryInterface) {
                        $this->setValue($owner, $value);
                    }
                }
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        event(new NestedElementsSaved(
            manager: $this,
            elements: $elements,
        ));
    }

    /**
     * @param  int[]  $except
     */
    private function deleteOtherNestedElements(ElementInterface $owner, array $except): void
    {
        $query = $this->nestedElementQuery($owner)
            ->drafts(null)
            ->canonicalsOnly()
            ->savedDraftsOnly(false)
            ->status(null)
            ->siteId($owner->siteId);

        $elements = $query->whereNotIn('elements.id', $except)->all();

        $deleteOwnership = [];

        /** @var NestedElementInterface[] $elements */
        foreach ($elements as $element) {
            if ($element->getPrimaryOwnerId() === $owner->id) {
                $hardDelete = $element->getIsUnpublishedDraft();
                Elements::deleteElement($element, $hardDelete);
            } else {
                $deleteOwnership[] = $element->id;
            }
        }

        if ($deleteOwnership) {
            DB::table(Table::ELEMENTS_OWNERS)
                ->whereIn('elementId', $deleteOwnership)
                ->where('ownerId', $owner->id)
                ->delete();
        }
    }

    public function duplicateNestedElements(
        ElementInterface $source,
        ElementInterface $target,
        bool $checkOtherSites = false,
        bool $deleteOtherNestedElements = true,
        bool $force = false,
    ): void {
        $elements = $this->getValue($source, true);
        if ($elements instanceof ElementQueryInterface) {
            $elements = ElementCollection::make($elements->getResultOverride() ?? $elements->all());
        }

        $elements = $elements
            ->filter(fn (ElementInterface $element) => isset($element->id))
            ->values()
            ->all();

        /** @var NestedElementInterface[] $elements */
        $this->setOwnerOnNestedElements($source, $elements);

        $newElementIds = [];

        DB::beginTransaction();
        try {
            // Only set the canonicalId if the target owner element is a derivative
            // and if the target's canonical element is not the same as target element, see
            // https://app.frontapp.com/open/msg_ukaoki1?key=U6zkE_S6_ApMXn3ntPMwUxSLe0sUPsmY for more info
            $setCanonicalId = $target->getIsDerivative() && $target->getCanonical()->id !== $target->id;

            /** @var NestedElementInterface[] $elements */
            foreach ($elements as $element) {
                $newAttributes = [
                    'canonicalId' => $setCanonicalId ? ($element->getCanonical()->getCanonicalId() ?? $element->id) : null,
                    'primaryOwner' => $target,
                    'owner' => $target,
                    'propagating' => false,
                    'resaving' => false,
                    'sortOrder' => $element->getSortOrder(),
                ];

                if ($element::isLocalized()) {
                    $newAttributes['siteId'] = $target->siteId;
                }

                /** @var NestedElementInterface $canonical */
                $canonical = $element->getCanonical(true);

                if (
                    $target->updatingFromDerivative &&
                    $element->getIsDerivative() &&
                    (
                        ElementHelper::isRevision($source) ||
                        (
                            $element->getPrimaryOwnerId() === $source->id &&
                            $canonical->getPrimaryOwnerId() === $target->id
                        )
                    )
                ) {
                    if (
                        ElementHelper::isRevision($source) ||
                        ! empty($target->newSiteIds) ||
                        (! $source::trackChanges() || $this->isModified($source, true))
                    ) {
                        $newElementId = Elements::updateCanonicalElement($element, $newAttributes)->id;
                        DB::table(Table::ELEMENTS_OWNERS)
                            ->upsert(
                                values: [
                                    'elementId' => $newElementId,
                                    'ownerId' => $target->id,
                                    'sortOrder' => $element->getSortOrder(),
                                ],
                                uniqueBy: ['elementId', 'ownerId'],
                                update: [
                                    'sortOrder' => $element->getSortOrder(),
                                ],
                            );
                    } else {
                        // if the canonical element is owned by the target element, then go with its ID
                        if ($canonical->getOwnerId() === $target->id) {
                            $newElementId = $element->getCanonicalId();
                        } else {
                            $newElementId = $element->id;
                        }
                    }
                } elseif (! $force && $element->getPrimaryOwnerId() === $target->id) {
                    DB::table(Table::ELEMENTS_OWNERS)
                        ->upsert(
                            values: [
                                'elementId' => $element->id,
                                'ownerId' => $target->id,
                                'sortOrder' => $element->getSortOrder(),
                            ],
                            uniqueBy: ['elementId', 'ownerId'],
                            update: [
                                'sortOrder' => $element->getSortOrder(),
                            ],
                        );

                    $newElementId = $element->id;
                } else {
                    $newElementId = Elements::duplicateElement($element, $newAttributes)->id;
                }

                $newElementIds[$element->id] = $newElementId;
            }

            event(new NestedElementsDuplicated(
                manager: $this,
                source: $source,
                target: $target,
                newElementIds: $newElementIds,
            ));

            if ($deleteOtherNestedElements) {
                $this->deleteOtherNestedElements($target, array_values($newElementIds));
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        if ($checkOtherSites && $this->propagationMethod !== PropagationMethod::All) {
            $targetSiteIds = array_map(
                fn (array $siteInfo) => $siteInfo['siteId'],
                ElementHelper::supportedSitesForElement($target),
            );
            $fieldSiteIds = $this->getSupportedSiteIds($target);
            $otherSiteIds = array_diff($targetSiteIds, $fieldSiteIds);

            if (! empty($otherSiteIds)) {
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

                $handledSiteIds = [];

                foreach ($otherSources as $otherSource) {
                    if (! isset($otherTargets[$otherSource->siteId])) {
                        continue;
                    }

                    if (in_array($otherSource->siteId, $handledSiteIds, true)) {
                        continue;
                    }

                    $otherTargets[$otherSource->siteId]->updatingFromDerivative = $target->updatingFromDerivative;
                    $this->duplicateNestedElements($otherSource, $otherTargets[$otherSource->siteId]);

                    $sourceSupportedSiteIds = $this->getSupportedSiteIds($otherSource);
                    $handledSiteIds = array_merge($handledSiteIds, $sourceSupportedSiteIds);
                }
            }
        }
    }

    /**
     * Returns an owner-specific element that can be safely mutated from an embedded index.
     * Existing primary-owner elements retain their identity.
     */
    public static function prepareElementForOwner(
        NestedElementInterface $element,
        ElementInterface $owner,
    ): NestedElementInterface {
        if ($element->getPrimaryOwnerId() === $owner->id) {
            return $element;
        }

        $canonicalId = $owner->getIsDerivative() && ElementHelper::belongsToCanonicalOwner($element, $owner)
            ? $element->getCanonicalId()
            : null;

        /** @var NestedElementInterface $copy */
        $copy = Elements::duplicateElement($element, [
            'canonicalId' => $canonicalId,
            'primaryOwner' => $owner,
            'owner' => $owner,
            'propagating' => false,
            'resaving' => false,
            'sortOrder' => $element->getSortOrder(),
            ...($element::isLocalized() ? ['siteId' => $owner->siteId] : []),
        ]);

        WorkflowsFacade::withContentChangeLock(
            DraftsFacade::getDraftIdsForElement($owner),
            fn () => DB::table(Table::ELEMENTS_OWNERS)
                ->where('elementId', $element->id)
                ->where('ownerId', $owner->id)
                ->delete(),
        );

        return $copy;
    }

    private function createRevisions(ElementInterface $canonical, ElementInterface $revision): void
    {
        $siteIds = array_map(
            fn (array $siteInfo) => $siteInfo['siteId'],
            ElementHelper::supportedSitesForElement($canonical),
        );

        /** @var NestedElementInterface[] $elements */
        $elements = [];
        $processedElementIds = [];

        foreach ($siteIds as $siteId) {
            if ($siteId === $canonical->siteId) {
                $owner = $canonical;
            } else {
                $owner = $canonical::find()
                    ->id($canonical->id)
                    ->siteId($siteId)
                    ->status(null)
                    ->one();

                if ($owner === null) {
                    continue;
                }
            }

            $siteElements = $this->nestedElementQuery($owner)
                ->status(null)
                ->all();

            /** @var NestedElementInterface $element */
            foreach ($siteElements as $element) {
                if (! isset($processedElementIds[$element->id])) {
                    $processedElementIds[$element->id] = true;
                    $elements[] = $element;
                }
            }
        }

        $revisionsService = app(Revisions::class);
        $elementRevisionIds = [];
        $ownershipData = [];
        $map = [];

        foreach ($elements as $element) {
            $elementRevisionId = $elementRevisionIds[] = $revisionsService->createRevision($element, null, null, [
                'primaryOwnerId' => $revision->id,
                'saveOwnership' => false,
            ]);
            $ownershipData[] = [
                'elementId' => $elementRevisionId,
                'ownerId' => $revision->id,
                'sortOrder' => $element->getSortOrder(),
            ];
            $map[$element->id] = $elementRevisionId;
        }

        DB::table(Table::ELEMENTS_OWNERS)
            ->where('ownerId', $revision->id)
            ->whereIn('elementId', $elementRevisionIds)
            ->delete();

        DB::table(Table::ELEMENTS_OWNERS)->insert($ownershipData);

        if (! empty($map)) {
            event(new NestedElementRevisionsCreated(
                manager: $this,
                source: $canonical,
                target: $revision,
                newElementIds: $map,
            ));
        }
    }

    private function mergeCanonicalChanges(ElementInterface $owner): void
    {
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

        $canonicalOwners = $owner::find()
            ->id($owner->getCanonicalId())
            ->siteId(array_keys($localizedOwners))
            ->status(null)
            ->ignorePlaceholders()
            ->all();

        $handledSiteIds = [];

        foreach ($canonicalOwners as $canonicalOwner) {
            if (isset($handledSiteIds[$canonicalOwner->siteId])) {
                continue;
            }

            /** @var NestedElementInterface[] $canonicalElements */
            $canonicalElements = $this->nestedElementQuery($canonicalOwner)
                ->siteId($canonicalOwner->siteId)
                ->status(null)
                ->trashed(null)
                ->ignorePlaceholders()
                ->all();

            /** @var NestedElementInterface[] $derivativeElements */
            $derivativeElements = $this->nestedElementQuery($owner)
                ->siteId($canonicalOwner->siteId)
                ->status(null)
                ->trashed(null)
                ->ignorePlaceholders()
                ->indexBy('canonicalId')
                ->all();

            foreach ($canonicalElements as $canonicalElement) {
                if (isset($derivativeElements[$canonicalElement->id])) {
                    $derivativeElement = $derivativeElements[$canonicalElement->id];

                    if ($canonicalElement->trashed) {
                        if ($derivativeElement->dateUpdated == $derivativeElement->dateCreated) {
                            Elements::deleteElement($derivativeElement);
                        }
                    } elseif (
                        ! $derivativeElement->trashed &&
                        $derivativeElement::trackChanges() &&
                        ElementHelper::isOutdated($derivativeElement)
                    ) {
                        Elements::mergeCanonicalChanges($derivativeElement);
                    }
                } elseif (! $canonicalElement->trashed && $canonicalElement->dateCreated > $owner->dateCreated) {
                    // This is a new nested element, so duplicate its ownership into the derivative
                    DB::table(Table::ELEMENTS_OWNERS)->upsert(
                        values: [
                            'elementId' => $canonicalElement->id,
                            'ownerId' => $owner->id,
                            'sortOrder' => $canonicalElement->getSortOrder(),
                        ],
                        uniqueBy: ['elementId', 'ownerId'],
                        update: ['sortOrder' => $canonicalElement->getSortOrder()],
                    );
                }
            }

            // Keep track of the sites we've already covered
            $siteIds = $this->getSupportedSiteIds($canonicalOwner);
            foreach ($siteIds as $siteId) {
                $handledSiteIds[$siteId] = true;
            }
        }
    }

    public function deleteNestedElements(ElementInterface $owner, bool $hardDelete = false): void
    {
        foreach (Sites::getAllSiteIds() as $siteId) {
            $query = $this->nestedElementQuery($owner)
                ->status(null)
                ->siteId($siteId);
            if ($hardDelete) {
                $query->trashed(null);
            }
            $query->{$this->ownerIdParam} = null;
            $query->{$this->primaryOwnerIdParam} = $owner->id;

            /** @var NestedElementInterface[] $elements */
            $elements = $query->all();

            foreach ($elements as $element) {
                if ($element->getIsRevision() && ! isset($element->dateDeleted)) {
                    $newOwnerId = DB::table(Table::ELEMENTS_OWNERS)
                        ->where('elementId', $element->id)
                        ->where('ownerId', '!=', $owner->id)
                        ->orderBy('ownerId')
                        ->value('ownerId');

                    if ($newOwnerId) {
                        $element->setPrimaryOwnerId($newOwnerId);
                        Elements::saveElement($element);

                        continue;
                    }
                }

                $element->deletedWithOwner = true;
                Elements::deleteElement($element, $hardDelete);
            }
        }
    }

    public function restoreNestedElements(ElementInterface $owner): void
    {
        foreach (ElementHelper::supportedSitesForElement($owner) as $siteInfo) {
            $query = $this->nestedElementQuery($owner)
                ->status(null)
                ->siteId($siteInfo['siteId'])
                ->trashed()
                ->where('elements.deletedWithOwner', true);

            $query->{$this->ownerIdParam} = null;
            $query->{$this->primaryOwnerIdParam} = $owner->id;

            Elements::restoreElements($query->all());
        }
    }
}
