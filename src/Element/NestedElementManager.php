<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use Closure;
use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Cp\Html\ElementIndexHtml;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Actions\ChangeSortOrder;
use CraftCms\Cms\Element\Actions\MoveDown;
use CraftCms\Cms\Element\Actions\MoveUp;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Element\Events\AfterSaveNestedElements;
use CraftCms\Cms\Element\Events\CreateNestedElementRevisions;
use CraftCms\Cms\Element\Events\DuplicateNestedElementsEvent;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use Generator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

use function CraftCms\Cms\renderObjectTemplate;
use function CraftCms\Cms\t;

/**
 * This can be used by elements or fields to manage nested elements, such as users -> addresses,
 * or Matrix fields -> nested entries.
 *
 * If this is for a custom field, [[field]] must be set. Otherwise, [[attribute]] must be set.
 */
class NestedElementManager extends Component
{
    private const string VIEW_MODE_CARDS = 'cards';

    private const string VIEW_MODE_INDEX = 'index';

    private static array $renderedPropagationFormats = [];

    /**
     * @param  class-string<NestedElementInterface>  $elementType
     * @param  Closure(ElementInterface): ElementQueryInterface  $queryFactory
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

    public array $criteria = [];

    public ?Closure $valueGetter = null;

    public Closure|null|false $valueSetter = null;

    public PropagationMethod $propagationMethod = PropagationMethod::All;

    public ?string $propagationKeyFormat = null;

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
            if (! isset(self::$renderedPropagationFormats[$cacheKey])) {
                self::$renderedPropagationFormats[$cacheKey] = renderObjectTemplate($this->propagationKeyFormat, $owner);
            }
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

    public function getCardsHtml(?ElementInterface $owner, array $config = []): string
    {
        $config += [
            'showInGrid' => false,
            'prevalidate' => false,
            'selectable' => false,
        ];

        return $this->createView(
            $owner,
            $config,
            self::VIEW_MODE_CARDS,
            function (string $id, array $config, $attribute, &$settings) use ($owner) {
                $settings += [
                    'deleteLabel' => mb_ucfirst(t('Delete {type}', [
                        'type' => $this->elementType::lowerDisplayName(),
                    ])),
                    'deleteConfirmationMessage' => t('Are you sure you want to delete the selected {type}?', [
                        'type' => $this->elementType::lowerDisplayName(),
                    ]),
                    'showInGrid' => $config['showInGrid'],
                    'selectable' => $config['selectable'],
                ];

                $html = Html::beginTag('div', options: [
                    'id' => $id,
                    'class' => 'nested-element-cards',
                ]);

                /** @var ElementQueryInterface|ElementCollection $value */
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
                            $element->setScenario(Element::SCENARIO_LIVE);
                        }
                        $element->validate();
                    }
                }

                $this->setOwnerOnNestedElements($owner, $elements);

                if (! empty($elements)) {
                    $html .= Html::ul()->items(...array_map(
                        fn (ElementInterface $element) => Html::li(app(ElementHtml::class)->elementCardHtml($element, [
                            'context' => 'field',
                            'showActionMenu' => true,
                            'selectable' => $config['selectable'],
                            'sortable' => $config['sortable'],
                            'showInGrid' => $config['showInGrid'] ?? false,
                        ]))->encode(false),
                        $elements,
                    ))->class(
                        'elements',
                        $config['showInGrid'] ? 'card-grid' : 'cards',
                        $config['prevalidate'] ? 'prevalidate' : ''
                    )->render();
                }

                $html .= Html::tag('div', t('Nothing yet.'), [
                    'class' => array_keys(array_filter([
                        'pane' => true,
                        'no-border' => true,
                        'zilch' => true,
                        'small' => true,
                        'hidden' => ! empty($elements),
                    ])),
                ]);

                return $html.Html::endTag('div');
            },
        );
    }

    public function getIndexHtml(?ElementInterface $owner, array $config = []): string
    {
        $config += [
            'allowedViewModes' => null,
            'showHeaderColumn' => true,
            'fieldLayouts' => [],
            'defaultSort' => null,
            'defaultTableColumns' => null,
            'prevalidate' => false,
            'pageSize' => 50,
            'storageKey' => null,
            'defaultViewMode' => 'cards',
            'static' => $owner?->getIsRevision(),
        ];

        if ($config['storageKey'] === null) {
            if (isset($this->field)) {
                if ($this->field::isMultiInstance()) {
                    if (isset($this->field->layoutElement)) {
                        $config['storageKey'] = sprintf('field:%s', $this->field->layoutElement->uid);
                    }
                } else {
                    $config['storageKey'] = sprintf('field:%s', $this->field->uid);
                }
            } elseif ($owner !== null) {
                $config['storageKey'] = sprintf('%s:%s', $owner::class, $this->attribute);
            }
        }

        return $this->createView(
            $owner,
            $config,
            self::VIEW_MODE_INDEX,
            function (string $id, array $config, string $attribute, array &$settings) use ($owner): string {
                $criteria = [
                    $this->ownerIdParam => $owner->id,
                ];

                if ($owner->getIsRevision()) {
                    $criteria['revisions'] = null;
                    $criteria['trashed'] = null;
                    $criteria['drafts'] = false;
                }

                $settings['indexSettings'] = [
                    'namespace' => InputNamespace::get(),
                    'allowedViewModes' => $config['allowedViewModes']
                        ? array_map(fn ($mode) => Str::toString($mode), $config['allowedViewModes'])
                        : null,
                    'showHeaderColumn' => $config['showHeaderColumn'],
                    'criteria' => array_merge($criteria, $this->criteria),
                    'batchSize' => $config['pageSize'],
                    'actions' => [],
                    'canHaveDrafts' => $config['canHaveDrafts'] ?? $this->elementType::hasDrafts(),
                    'storageKey' => $config['storageKey'],
                    'static' => $config['static'],
                ];

                if (! $config['static'] && $config['sortable']) {
                    HtmlStack::startJsBuffer();
                    $actionConfig = ElementHelper::actionConfig(new ChangeSortOrder($owner, $attribute));
                    $actionConfig['bodyHtml'] = HtmlStack::clearJsBuffer();
                    $settings['indexSettings']['actions'][] = $actionConfig;

                    HtmlStack::startJsBuffer();
                    $actionConfig = ElementHelper::actionConfig(new MoveUp($owner, $attribute));
                    $actionConfig['bodyHtml'] = HtmlStack::clearJsBuffer();
                    $settings['indexSettings']['actions'][] = $actionConfig;

                    HtmlStack::startJsBuffer();
                    $actionConfig = ElementHelper::actionConfig(new MoveDown($owner, $attribute));
                    $actionConfig['bodyHtml'] = HtmlStack::clearJsBuffer();
                    $settings['indexSettings']['actions'][] = $actionConfig;
                }

                return app(ElementIndexHtml::class)->html($this->elementType, [
                    'class' => [$config['prevalidate'] ? 'prevalidate' : ''],
                    'context' => 'embedded-index',
                    'defaultSort' => $config['defaultSort'],
                    'defaultTableColumns' => $config['defaultTableColumns'],
                    'defaultViewMode' => $config['defaultViewMode'],
                    'fieldLayouts' => $config['fieldLayouts'],
                    'id' => $id,
                    'prevalidate' => $config['prevalidate'] ?? false,
                    'registerJs' => false,
                    'showSiteMenu' => false,
                    'sources' => false,
                ]);
            },
        );
    }

    private function createView(?ElementInterface $owner, array $config, string $mode, callable $renderHtml): string
    {
        if (! $owner?->id) {
            $message = t('{nestedType} can only be created after the {ownerType} has been saved.', [
                'nestedType' => $this->elementType::pluralDisplayName(),
                'ownerType' => $owner ? $owner::lowerDisplayName() : t('element'),
            ]);

            return Html::tag('div', $message, ['class' => 'pane no-border zilch small']);
        }

        $config += [
            'sortable' => false,
            'canCreate' => false,
            'canPaste' => false,
            'createButtonLabel' => null,
            'createAttributes' => null,
            'minElements' => null,
            'maxElements' => null,
        ];

        if ($config['createButtonLabel'] === null) {
            $config['createButtonLabel'] = t('New {type}', [
                'type' => $this->elementType::lowerDisplayName(),
            ]);
        }

        $authorizedOwnerId = $owner->id;
        if ($owner->isProvisionalDraft && $owner->draftCreatorId === Auth::user()?->id) {
            /** @var ElementInterface $owner */
            $authorizedOwnerId = $owner->getCanonicalId();
        }

        $attribute = $this->attribute ?? sprintf('field:%s', $this->field->handle);
        SessionAuth::authorize(sprintf('manageNestedElements::%s::%s', $authorizedOwnerId, $attribute));

        return InputNamespace::namespaceInputs(function () use ($mode, $attribute, $owner, $config, $renderHtml) {
            $id = sprintf('element-index-%s', mt_rand());

            $settings = [
                'mode' => $mode,
                'ownerElementType' => $owner::class,
                'ownerId' => $owner->id,
                'ownerSiteId' => $owner->siteId,
                'attribute' => $attribute,
                'sortable' => $config['sortable'],
                'canCreate' => $config['canCreate'],
                'canPaste' => $config['canPaste'],
                'minElements' => $config['minElements'],
                'maxElements' => $config['maxElements'],
                'createButtonLabel' => $config['createButtonLabel'],
                'ownerIdParam' => $this->ownerIdParam,
                'fieldId' => $this->field?->id,
                'fieldHandle' => $this->field?->handle,
                'baseInputName' => InputNamespace::get(),
                'prevalidate' => $config['prevalidate'] ?? false,
            ];

            if (! empty($config['createAttributes'])) {
                $settings['createAttributes'] = $config['createAttributes'];
                if (Arr::isIndexed($settings['createAttributes'])) {
                    if (count($settings['createAttributes']) === 1) {
                        $settings['createAttributes'] = Arr::first($settings['createAttributes'])['attributes'];
                    } else {
                        $settings['createAttributes'] = array_map(function (array $attributes) {
                            if (isset($attributes['icon'])) {
                                $attributes['icon'] = Icons::svg($attributes['icon']);
                            }
                            if (isset($attributes['color']) && $attributes['color'] instanceof Color) {
                                $attributes['color'] = $attributes['color']->value;
                            }

                            return $attributes;
                        }, $settings['createAttributes']);
                    }
                }
            }

            $html = $renderHtml($id, $config, $attribute, $settings);

            HtmlStack::jsWithVars(fn ($id, $elementType, $settings) => <<<JS
(() => {
  new Craft.NestedElementManager('#' + $id, $elementType, $settings)
})();
JS, [
                InputNamespace::namespaceId($id),
                $this->elementType,
                $settings,
            ]);

            return $html;
        }, Html::id($this->field->handle ?? $attribute));
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
            return $owner->errors()->has("$this->attribute.*");
        }

        foreach ($this->fieldInstances($owner) as $instance) {
            /** @var FieldInterface $instance */
            if ($owner->errors()->has("$instance->handle.*")) {
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
                    Elements::saveElement($element, false);

                    if (
                        $element->getPrimaryOwnerId() === $owner->id &&
                        $element->getIsDraft() &&
                        ! $element->getIsUnpublishedDraft() &&
                        ! $owner->getIsCanonical() &&
                        ! $owner->getIsUnpublishedDraft()
                    ) {
                        /** @var NestedElementInterface $canonical */
                        $canonical = $element->getCanonical(true);
                        if ($canonical->getPrimaryOwnerId() === $owner->getCanonicalId()) {
                            app(Drafts::class)->removeDraftData($element);
                            DB::table(Table::ELEMENTS_OWNERS)
                                ->where([
                                    'elementId' => $canonical->id,
                                    'ownerId' => $owner->id,
                                ])
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

            $this->deleteOtherNestedElements($owner, $elementIds);

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
                    $localizedOwners = $owner::find()
                        ->drafts($owner->getIsDraft())
                        ->provisionalDrafts($owner->isProvisionalDraft)
                        ->revisions($owner->getIsRevision())
                        ->id($owner->id)
                        ->siteId($otherSiteIds)
                        ->status(null)
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
                            $preexistingLocalizedOwner = $owner::find()
                                ->drafts($owner->getIsDraft())
                                ->provisionalDrafts($owner->isProvisionalDraft)
                                ->revisions($owner->getIsRevision())
                                ->id($owner->id)
                                ->siteId($sharedPreexistingOtherSiteIds)
                                ->status(null)
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

        event(new AfterSaveNestedElements(
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

        /** @var ElementCollection<NestedElementInterface> $elements */
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

                if ($target->updatingFromDerivative && $element->getIsDerivative()) {
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
                        // If we're updating the canonical owner element, then go with the nested element’s canonical ID.
                        // Otherwise, leave the current ID intact.
                        $newElementId = $target->getIsCanonical() ? $element->getCanonicalId() : $element->id;
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

            event(new DuplicateNestedElementsEvent(
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

    private function createRevisions(ElementInterface $canonical, ElementInterface $revision): void
    {
        $siteIds = array_map(
            fn (array $siteInfo) => $siteInfo['siteId'],
            ElementHelper::supportedSitesForElement($canonical),
        );

        /** @var NestedElementInterface[] $elements */
        $elements = $this->nestedElementQuery($canonical)
            ->siteId($siteIds)
            ->preferSites([$canonical->siteId])
            ->unique()
            ->status(null)
            ->all();

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
            event(new CreateNestedElementRevisions(
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
                    } elseif (! $derivativeElement->trashed && ElementHelper::isOutdated($derivativeElement)) {
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
