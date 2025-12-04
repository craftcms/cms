<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use Closure;
use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\base\NestedElementInterface;
use craft\behaviors\DraftBehavior;
use craft\db\Query;
use craft\db\Table;
use craft\elements\actions\ChangeSortOrder;
use craft\elements\actions\MoveDown;
use craft\elements\actions\MoveUp;
use craft\elements\db\ElementQueryInterface;
use craft\enums\Color;
use craft\enums\PropagationMethod;
use craft\events\BulkElementsEvent;
use craft\events\DuplicateNestedElementsEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\models\Site;
use Generator;
use Throwable;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * Nested Element Manager
 *
 * This can be used by elements or fields to manage nested elements, such as users → addresses,
 * or Matrix fields → nested entries.
 *
 * If this is for a custom field, [[field]] must be set. Otherwise, [[attribute]] must be set.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class NestedElementManager extends Component
{
    private const VIEW_MODE_CARDS = 'cards';
    private const VIEW_MODE_INDEX = 'index';

    /**
     * @event BulkElementsEvent The event that is triggered after nested elements are resaved.
     */
    public const EVENT_AFTER_SAVE_ELEMENTS = 'afterSaveElements';

    /**
     * @event DuplicateNestedElementsEvent The event that is triggered after nested elements are duplicated.
     */
    public const EVENT_AFTER_DUPLICATE_NESTED_ELEMENTS = 'afterDuplicateNestedElements';

    /**
     * @event DuplicateNestedElementsEvent The event that is triggered after revisions are created for nested elements.
     * @see createRevisions()
     */
    public const EVENT_AFTER_CREATE_REVISIONS = 'afterCreateRevisions';

    /**
     * @see getSupportedSiteIds()
     */
    private static array $renderedPropagationFormats = [];

    /**
     * Constructor
     *
     * @param class-string<NestedElementInterface> $elementType The nested element type.
     * @param Closure(ElementInterface $owner): ElementQueryInterface $queryFactory A factory method which returns a
     * query for fetching nested elements
     * @param array $config name-value pairs that will be used to initialize the object properties.
     */
    public function __construct(
        private readonly string $elementType,
        private readonly Closure $queryFactory,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    /**
     * @var string|null The attribute name used to access nested elements.
     */
    public ?string $attribute = null;

    /**
     * @var FieldInterface|null The field associated with this nested element manager.
     */
    public ?FieldInterface $field = null;

    /**
     * @var string The name of the element query param that nested elements use to associate with the owner’s ID
     */
    public string $ownerIdParam = 'ownerId';

    /**
     * @var string The name of the element query param that nested elements use to associate with the primary owner’s ID
     */
    public string $primaryOwnerIdParam = 'primaryOwnerId';

    /**
     * @var array Additional element query params that should be set when fetching nested elements.
     */
    public array $criteria = [];

    /**
     * @var Closure|null Closure that will get the value.
     */
    public Closure|null $valueGetter = null;

    /**
     * @var Closure|null|false Closure that will update the value.
     */
    public Closure|null|false $valueSetter = null;

    /**
     * @var PropagationMethod The propagation method that the nested elements should use.
     *
     *  This can be set to one of the following:
     *
     *  - [[PropagationMethod::None]] – Only save elements in the site they were created in
     *  - [[PropagationMethod::SiteGroup]] – Save elements to other sites in the same site group
     *  - [[PropagationMethod::Language]] – Save elements to other sites with the same language
     *  - [[PropagationMethod::Custom]] – Save elements to other sites based on a custom [[$propagationKeyFormat|propagation key format]]
     *  - [[PropagationMethod::All]] – Save elements to all sites supported by the owner element
     */
    public PropagationMethod $propagationMethod = PropagationMethod::All;

    /**
     * @var string|null The propagation key format that the nested elements should use,
     * if [[$propagationMethod]] is set to [[PropagationMethod::Custom]].
     */
    public ?string $propagationKeyFormat = null;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (!isset($this->attribute) && !isset($this->field)) {
            throw new InvalidConfigException('NestedElementManager requires that either `attribute` or `field` is set.');
        }
        if (isset($this->attribute) && isset($this->field)) {
            throw new InvalidConfigException('NestedElementManager requires that either `attribute` or `field` is set, but not both.');
        }
    }

    /**
     * Returns whether the field or attribute should be shown as translatable in the UI, for the given owner element.
     *
     * @param ElementInterface|null $owner
     * @return bool
     */
    public function getIsTranslatable(?ElementInterface $owner = null): bool
    {
        if ($this->propagationMethod === PropagationMethod::Custom && $this->propagationKeyFormat !== null) {
            return (
                $owner === null ||
                Craft::$app->getView()->renderObjectTemplate($this->propagationKeyFormat, $owner) !== ''
            );
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

        if (!$query instanceof ElementQueryInterface) {
            $query = $this->nestedElementQuery($owner);
        }

        if ($fetchAll && $query->getCachedResult() === null) {
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
     * @param ElementInterface $owner
     * @param NestedElementInterface[] $elements
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

    /**
     * Returns the search keywords for nested elements of the given owner element.
     *
     * @param ElementInterface $owner
     * @return string
     */
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

        return StringHelper::toString($keywords, ' ');
    }

    /**
     * Returns the description of this field or attribute’s translation support, for the given owner element.
     *
     * @param ElementInterface|null $owner
     * @return string|null
     */
    public function getTranslationDescription(?ElementInterface $owner = null): ?string
    {
        if (!$owner) {
            return null;
        }

        switch ($this->propagationMethod) {
            case PropagationMethod::None:
                return Craft::t('app', '{type} will only be saved in the {site} site.', [
                    'type' => $this->elementType::pluralDisplayName(),
                    'site' => Craft::t('site', $owner->getSite()->getName()),
                ]);
            case PropagationMethod::SiteGroup:
                return Craft::t('app', '{type} will be saved across all sites in the {group} site group.', [
                    'type' => $this->elementType::pluralDisplayName(),
                    'group' => Craft::t('site', $owner->getSite()->getGroup()->getName()),
                ]);
            case PropagationMethod::Language:
                $language = Craft::$app->getI18n()->getLocaleById($owner->getSite()->language)
                    ->getDisplayName(Craft::$app->language);
                return Craft::t('app', '{type} will be saved across all {language}-language sites.', [
                    'type' => $this->elementType::pluralDisplayName(),
                    'language' => $language,
                ]);
            default:
                return null;
        }
    }

    /**
     * Returns the site IDs that are supported by nested elements for the given owner element.
     *
     * @param ElementInterface $owner
     * @return int[]
     * @since 5.0.0
     */
    public function getSupportedSiteIds(ElementInterface $owner): array
    {
        /** @var Site[] $allSites */
        $allSites = ArrayHelper::index(Craft::$app->getSites()->getAllSites(), 'id');
        $ownerSiteIds = array_map(
            fn(array $siteInfo) => $siteInfo['siteId'],
            ElementHelper::supportedSitesForElement($owner),
        );
        $siteIds = [];

        $view = Craft::$app->getView();
        $elementsService = Craft::$app->getElements();

        if ($this->propagationMethod === PropagationMethod::Custom && $this->propagationKeyFormat !== null) {
            $cacheKey = sprintf('%s-%s-%s', md5($this->propagationKeyFormat), $owner->id, $owner->siteId);
            if (!isset(self::$renderedPropagationFormats[$cacheKey])) {
                self::$renderedPropagationFormats[$cacheKey] = $view->renderObjectTemplate($this->propagationKeyFormat, $owner);
            }
            $propagationKey = self::$renderedPropagationFormats[$cacheKey];
        }

        foreach ($ownerSiteIds as $siteId) {
            switch ($this->propagationMethod) {
                case PropagationMethod::None:
                    $include = $siteId == $owner->siteId;
                    break;
                case PropagationMethod::SiteGroup:
                    $include = $allSites[$siteId]->groupId == $allSites[$owner->siteId]->groupId;
                    break;
                case PropagationMethod::Language:
                    $include = $allSites[$siteId]->language == $allSites[$owner->siteId]->language;
                    break;
                case PropagationMethod::Custom:
                    if (!isset($propagationKey)) {
                        $include = true;
                    } else {
                        $cacheKey = sprintf('%s-%s-%s', md5($this->propagationKeyFormat), $owner->id, $siteId);
                        if (!isset(self::$renderedPropagationFormats[$cacheKey])) {
                            $siteOwner = $elementsService->getElementById($owner->id, get_class($owner), $siteId);
                            self::$renderedPropagationFormats[$cacheKey] = $siteOwner
                                ? $view->renderObjectTemplate($this->propagationKeyFormat, $siteOwner)
                                : false;
                        }
                        $include = $propagationKey === self::$renderedPropagationFormats[$cacheKey];
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
     * Returns the HTML for managing nested elements via cards.
     *
     * @param ElementInterface|null $owner
     * @param array $config
     * @return string
     */
    public function getCardsHtml(?ElementInterface $owner, array $config = []): string
    {
        $config += [
            'showInGrid' => false,
            'prevalidate' => false,
        ];

        return $this->createView(
            $owner,
            $config,
            self::VIEW_MODE_CARDS,
            function(string $id, array $config, $attribute, &$settings) use ($owner) {
                $settings += [
                    'deleteLabel' => StringHelper::upperCaseFirst(Craft::t('app', 'Delete {type}', [
                        'type' => $this->elementType::lowerDisplayName(),
                    ])),
                    'deleteConfirmationMessage' => Craft::t('app', 'Are you sure you want to delete the selected {type}?', [
                        'type' => $this->elementType::lowerDisplayName(),
                    ]),
                    'showInGrid' => $config['showInGrid'],
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
                    $elements = $value->getCachedResult() ?? $value
                        ->status(null)
                        ->limit(null)
                        ->all();
                }

                // See if there are any provisional drafts we should swap these out with
                ElementHelper::swapInProvisionalDrafts($elements);

                if ($this->hasErrors($owner)) {
                    foreach ($elements as $element) {
                        if ($element->enabled && $element->getEnabledForSite()) {
                            $element->setScenario(Element::SCENARIO_LIVE);
                        }
                        $element->validate();
                    }
                }

                $this->setOwnerOnNestedElements($owner, $elements);

                if (!empty($elements)) {
                    $html .= Html::ul(array_map(
                        fn(ElementInterface $element) => Cp::elementCardHtml($element, [
                            'context' => 'field',
                            'showActionMenu' => true,
                            'sortable' => $config['sortable'],
                            'showInGrid' => $config['showInGrid'] ?? false,
                            'hyperlink' => false,
                        ]),
                        $elements,
                    ), [
                        'encode' => false,
                        'class' => [
                            'elements',
                            $config['showInGrid'] ? 'card-grid' : 'cards',
                            $config['prevalidate'] ? 'prevalidate' : '',
                        ],
                    ]);
                }

                $html .=
                    Html::tag('div', Craft::t('app', 'Nothing yet.'), [
                        'class' => array_keys(array_filter([
                            'pane' => true,
                            'no-border' => true,
                            'zilch' => true,
                            'small' => true,
                            'hidden' => !empty($elements),
                        ])),
                    ]) .
                    Html::endTag('div');

                return $html;
            }
        );
    }

    /**
     * Returns the HTML for managing nested elements via an element index.
     *
     * @param ElementInterface|null $owner
     * @param array $config
     * @return string
     */
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
            'static' => $owner->getIsRevision(),
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
            function(string $id, array $config, string $attribute, array &$settings) use ($owner): string {
                $view = Craft::$app->getView();

                $criteria = [
                    $this->ownerIdParam => $owner->id,
                ];

                if ($owner->getIsRevision()) {
                    $criteria['revisions'] = null;
                    $criteria['trashed'] = null;
                    $criteria['drafts'] = false;
                }

                $settings['indexSettings'] = [
                    'namespace' => $view->getNamespace(),
                    'allowedViewModes' => $config['allowedViewModes']
                        ? array_map(fn($mode) => StringHelper::toString($mode), $config['allowedViewModes'])
                        : null,
                    'showHeaderColumn' => $config['showHeaderColumn'],
                    'criteria' => array_merge($criteria, $this->criteria),
                    'batchSize' => $config['pageSize'],
                    'actions' => [],
                    'canHaveDrafts' => $config['canHaveDrafts'] ?? $this->elementType::hasDrafts(),
                    'storageKey' => $config['storageKey'],
                    'static' => $config['static'],
                ];

                if (!$config['static'] && $config['sortable']) {
                    $view->startJsBuffer();
                    $actionConfig = ElementHelper::actionConfig(new ChangeSortOrder($owner, $attribute));
                    $actionConfig['bodyHtml'] = $view->clearJsBuffer();
                    $settings['indexSettings']['actions'][] = $actionConfig;

                    $view->startJsBuffer();
                    $actionConfig = ElementHelper::actionConfig(new MoveUp($owner, $attribute));
                    $actionConfig['bodyHtml'] = $view->clearJsBuffer();
                    $settings['indexSettings']['actions'][] = $actionConfig;

                    $view->startJsBuffer();
                    $actionConfig = ElementHelper::actionConfig(new MoveDown($owner, $attribute));
                    $actionConfig['bodyHtml'] = $view->clearJsBuffer();
                    $settings['indexSettings']['actions'][] = $actionConfig;
                }

                return Cp::elementIndexHtml($this->elementType, [
                    'context' => 'embedded-index',
                    'id' => $id,
                    'showSiteMenu' => false,
                    'sources' => false,
                    'fieldLayouts' => $config['fieldLayouts'],
                    'defaultSort' => $config['defaultSort'],
                    'defaultTableColumns' => $config['defaultTableColumns'],
                    'defaultViewMode' => $config['defaultViewMode'],
                    'registerJs' => false,
                    'class' => [$config['prevalidate'] ? 'prevalidate' : ''],
                    'prevalidate' => $config['prevalidate'] ?? false,
                ]);
            },
        );
    }

    private function createView(?ElementInterface $owner, array $config, string $mode, callable $renderHtml): string
    {
        if (!$owner?->id) {
            $message = Craft::t('app', '{nestedType} can only be created after the {ownerType} has been saved.', [
                'nestedType' => $this->elementType::pluralDisplayName(),
                'ownerType' => $owner ? $owner::lowerDisplayName() : Craft::t('app', 'element'),
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
            $config['createButtonLabel'] = Craft::t('app', 'New {type}', [
                'type' => $this->elementType::lowerDisplayName(),
            ]);
        }

        $authorizedOwnerId = $owner->id;
        if ($owner->isProvisionalDraft) {
            /** @var ElementInterface&DraftBehavior $owner */
            if ($owner->creatorId === Craft::$app->getUser()->getIdentity()?->id) {
                $authorizedOwnerId = $owner->getCanonicalId();
            }
        }
        $attribute = $this->attribute ?? sprintf('field:%s', $this->field->handle);
        Craft::$app->getSession()->authorize(sprintf('manageNestedElements::%s::%s', $authorizedOwnerId, $attribute));

        $view = Craft::$app->getView();
        return $view->namespaceInputs(function() use (
            $mode,
            $attribute,
            $view,
            $owner,
            $config,
            $renderHtml,
        ) {
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
                'baseInputName' => $view->getNamespace(),
                'prevalidate' => $config['prevalidate'] ?? false,
            ];

            if (!empty($config['createAttributes'])) {
                $settings['createAttributes'] = $config['createAttributes'];
                if (ArrayHelper::isIndexed($settings['createAttributes'])) {
                    if (count($settings['createAttributes']) === 1) {
                        $settings['createAttributes'] = ArrayHelper::firstValue($settings['createAttributes'])['attributes'];
                    } else {
                        $settings['createAttributes'] = array_map(function(array $attributes) {
                            if (isset($attributes['icon'])) {
                                $attributes['icon'] = Cp::iconSvg($attributes['icon']);
                            }
                            if (isset($attributes['color']) && $attributes['color'] instanceof Color) {
                                $attributes['color'] = $attributes['color']->value;
                            }
                            return $attributes;
                        }, $settings['createAttributes']);
                    }
                }
            }

            // render the HTML, and give the render function a chance to modify the JS settings
            $html = $renderHtml($id, $config, $attribute, $settings);

            $view->registerJsWithVars(fn($id, $elementType, $settings) => <<<JS
(() => {
  new Craft.NestedElementManager('#' + $id, $elementType, $settings);
})();
JS, [
                $view->namespaceInputId($id),
                $this->elementType,
                $settings,
            ]);

            return $html;
        }, Html::id($this->field->handle ?? $attribute));
    }

    /**
     * Maintains the nested elements after an owner element has been saved.
     *
     * This should be called from the element’s [[ElementInterface::afterPropagate()|afterPropagate()]] method,
     * or the field’s [[\craft\base\FieldInterface::afterElementPropagate()|afterElementPropagate()]] method.
     *
     * @param ElementInterface $owner
     * @param bool $isNew Whether the owner is a new element
     */
    public function maintainNestedElements(ElementInterface $owner, bool $isNew): void
    {
        $resetValue = false;

        if ($owner->duplicateOf !== null) {
            // If this is a draft, its nested element ownership will be duplicated by Drafts::createDraft()
            if ($owner->getIsRevision()) {
                $this->createRevisions($owner->duplicateOf, $owner);
            // getIsUnpublishedDraft is needed for "save as new" duplication
            } elseif (!$owner->getIsDraft() || $owner->getIsUnpublishedDraft()) {
                $this->duplicateNestedElements($owner->duplicateOf, $owner, true, !$isNew);
            }
            $resetValue = true;
        } elseif ($this->isDirty($owner) || !empty($owner->newSiteIds)) {
            $this->saveNestedElements($owner);
        } elseif ($owner->mergingCanonicalChanges) {
            $this->mergeCanonicalChanges($owner);
            $resetValue = true;
        }

        // Always reset the value if the owner is new
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
            return $owner->hasErrors("$this->attribute.*");
        }

        foreach ($this->fieldInstances($owner) as $instance) {
            /** @var FieldInterface $instance */
            if ($owner->hasErrors("$instance->handle.*")) {
                return true;
            }
        }

        return false;
    }

    private function fieldInstances(ElementInterface $owner): Generator
    {
        if (!isset($this->field)) {
            return;
        }

        if (!$this->field::isMultiInstance()) {
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

    private function saveNestedElements(ElementInterface $owner): void
    {
        $elementsService = Craft::$app->getElements();

        $value = $this->getValue($owner, true);
        if ($value instanceof ElementCollection) {
            $elements = $value->all();
            $saveAll = true;
        } else {
            $elements = $value->getCachedResult();
            if ($elements !== null) {
                $saveAll = !empty($owner->newSiteIds);
            } else {
                $elements = $value->all();
                $saveAll = true;
            }
        }

        /** @var NestedElementInterface[] $elements */
        $this->setOwnerOnNestedElements($owner, $elements);

        $elementIds = [];
        $sortOrder = 0;

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            /** @var NestedElementInterface[] $elements */
            foreach ($elements as $element) {
                // If it's soft-deleted, restore it.
                // (This could happen if the element didn't come back in getValue() previously,
                // but now it's showing up again, e.g. if an entry card was cut from a CKEditor field
                // and then pasted back in somewhere else.)
                if (isset($element->dateDeleted)) {
                    $elementsService->restoreElement($element);
                }

                $sortOrder++;
                if ($saveAll || !$element->id || $element->forceSave) {
                    $element->setOwner($owner);
                    $element->setSortOrder($sortOrder);
                    $element->resaving = $owner->resaving;
                    $elementsService->saveElement($element, false);

                    // If this element's primary owner is $owner, and it’s a draft of another element whose owner is
                    // $owner's canonical (e.g. a draft entry created by Matrix::_createEntriesFromSerializedData()),
                    // we can shed its draft data and relation with the canonical owner now
                    if (
                        $element->getPrimaryOwnerId() === $owner->id &&
                        $element->getIsDraft() &&
                        !$element->getIsUnpublishedDraft() &&
                        // $owner could be a draft or a non-canonical Matrix entry, etc.
                        (!$owner->getIsCanonical()) &&
                        !$owner->getIsUnpublishedDraft()
                    ) {
                        /** @var NestedElementInterface $canonical */
                        $canonical = $element->getCanonical(true);
                        if ($canonical->getPrimaryOwnerId() === $owner->getCanonicalId()) {
                            Craft::$app->getDrafts()->removeDraftData($element);
                            Db::delete(Table::ELEMENTS_OWNERS, [
                                'elementId' => $canonical->id,
                                'ownerId' => $owner->id,
                            ]);
                        }
                    } elseif (
                        $element->getIsUnpublishedDraft() &&
                        $element->getPrimaryOwnerId() === $owner->id
                    ) {
                        Craft::$app->getDrafts()->removeDraftData($element);
                    }
                } elseif ((int)$element->getSortOrder() !== $sortOrder) {
                    // Just update its sortOrder
                    $element->setSortOrder($sortOrder);
                    Db::update(Table::ELEMENTS_OWNERS, [
                        'sortOrder' => $sortOrder,
                    ], [
                        'elementId' => $element->id,
                        'ownerId' => $owner->id,
                    ], [], false);
                }

                $elementIds[] = $element->id;
            }

            // Delete any elements that shouldn't be there anymore
            $this->deleteOtherNestedElements($owner, $elementIds);

            // Should we duplicate the elements to other sites?
            if (
                $this->propagationMethod !== PropagationMethod::All &&
                ($owner->propagateAll || !empty($owner->newSiteIds))
            ) {
                // Find the owner's site IDs that *aren't* supported by this site's nested elements
                $ownerSiteIds = array_map(
                    fn(array $siteInfo) => $siteInfo['siteId'],
                    ElementHelper::supportedSitesForElement($owner),
                );
                $fieldSiteIds = $this->getSupportedSiteIds($owner);
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

                    // Duplicate elements, ensuring we don't process the same elements more than once
                    $handledSiteIds = [];

                    if ($value instanceof ElementQueryInterface) {
                        $cachedQuery = (clone $value)->status(null);
                        $cachedQuery->setCachedResult($elements);
                        $this->setValue($owner, $cachedQuery);
                    }

                    foreach ($localizedOwners as $localizedOwner) {
                        // Make sure we haven't already duplicated elements for this site, via propagation from another site
                        if (isset($handledSiteIds[$localizedOwner->siteId])) {
                            continue;
                        }

                        // Find all the source owner’s supported sites
                        $sourceSupportedSiteIds = $this->getSupportedSiteIds($localizedOwner);

                        // Do elements in this target happen to share supported sites with a preexisting site?
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
                            // Just resave elements for that one site, and let them propagate over to the new site(s) from there
                            $this->saveNestedElements($preexistingLocalizedOwner);
                        } else {
                            // Duplicate the elements, but **don't track** the duplications, so the edit page doesn’t think
                            // its elements have been replaced by the other sites’ nested elements
                            $this->duplicateNestedElements($owner, $localizedOwner, force: true);
                        }

                        // Make sure we don't duplicate elements for any of the sites that were just propagated to
                        foreach ($sourceSupportedSiteIds as $siteId) {
                            $handledSiteIds[$siteId] = true;
                        }
                    }

                    if ($value instanceof ElementQueryInterface) {
                        $this->setValue($owner, $value);
                    }
                }
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Fire a 'afterSaveElements' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_ELEMENTS)) {
            $this->trigger(self::EVENT_AFTER_SAVE_ELEMENTS, new BulkElementsEvent([
                'elements' => $elements,
            ]));
        }
    }

    /**
     * Deletes elements from an owner element
     *
     * @param ElementInterface $owner The owner element
     * @param int[] $except Element IDs that should be left alone
     */
    private function deleteOtherNestedElements(ElementInterface $owner, array $except): void
    {
        /** @var NestedElementInterface[] $elements */
        $elements = $this->nestedElementQuery($owner)
            ->drafts(null)
            ->canonicalsOnly()
            ->savedDraftsOnly(false)
            ->status(null)
            ->siteId($owner->siteId)
            ->andWhere(['not', ['elements.id' => $except]])
            ->all();

        $elementsService = Craft::$app->getElements();
        $deleteOwnership = [];

        foreach ($elements as $element) {
            if ($element->getPrimaryOwnerId() === $owner->id) {
                $hardDelete = $element->getIsUnpublishedDraft();
                $elementsService->deleteElement($element, $hardDelete);
            } else {
                // Just delete the ownership relation
                $deleteOwnership[] = $element->id;
            }
        }

        if ($deleteOwnership) {
            Db::delete(Table::ELEMENTS_OWNERS, [
                'elementId' => $deleteOwnership,
                'ownerId' => $owner->id,
            ]);
        }
    }

    /**
     * Duplicates nested elements from one owner to another.
     *
     * @param ElementInterface $source The source element that nested elements should be duplicated from
     * @param ElementInterface $target The target element that nested elements should be duplicated to
     * @param bool $checkOtherSites Whether to duplicate nested elements for the source element’s other supported sites
     * @param bool $deleteOtherNestedElements Whether to delete any nested elements that belong to the element,
     * which weren’t included in the duplication
     * @param bool $force Whether to force duplication, even if it looks like only the nested element ownership was duplicated
     */
    private function duplicateNestedElements(
        ElementInterface $source,
        ElementInterface $target,
        bool $checkOtherSites = false,
        bool $deleteOtherNestedElements = true,
        bool $force = false,
    ): void {
        $elementsService = Craft::$app->getElements();
        $elements = $this->getValue($source, true);
        if ($elements instanceof ElementQueryInterface) {
            $elements = ElementCollection::make($elements->getCachedResult() ?? $elements->all());
        }

        // Ignore any elements that don't have an ID yet
        /** @var ElementCollection<NestedElementInterface> $elements */
        $elements = $elements
            ->filter(fn(ElementInterface $element) => isset($element->id))
            ->values()
            ->all();

        /** @var NestedElementInterface[] $elements */
        $this->setOwnerOnNestedElements($source, $elements);

        $newElementIds = [];

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $setCanonicalId = $target->getIsDerivative() && $target->getCanonical()->id !== $target->id;

            /** @var NestedElementInterface[] $elements */
            foreach ($elements as $element) {
                $newAttributes = [
                    // Only set the canonicalId if the target owner element is a derivative
                    // and if the target's canonical element is not the same as target element, see
                    // https://app.frontapp.com/open/msg_ukaoki1?key=U6zkE_S6_ApMXn3ntPMwUxSLe0sUPsmY for more info
                    'canonicalId' => $setCanonicalId ? $element->id : null,
                    'primaryOwner' => $target,
                    'owner' => $target,
                    'propagating' => false,
                    'sortOrder' => $element->getSortOrder(),
                ];

                if ($element::isLocalized()) {
                    $newAttributes['siteId'] = $target->siteId;
                }

                if ($target->updatingFromDerivative && $element->getIsDerivative()) {
                    if (
                        ElementHelper::isRevision($source) ||
                        !empty($target->newSiteIds) ||
                        (!$source::trackChanges() || $this->isModified($source, true))
                    ) {
                        $newElementId = $elementsService->updateCanonicalElement($element, $newAttributes)->id;
                        // upsert newElementId in case it was removed from the ownership table before
                        // this will happen if we add a nested element to the owner & save,
                        // then remove that nested element & save,
                        // and then revert to the revision that still has that nested element
                        Db::upsert(Table::ELEMENTS_OWNERS, [
                            'elementId' => $newElementId,
                            'ownerId' => $target->id,
                            'sortOrder' => $element->getSortOrder(),
                        ], [
                            'sortOrder' => $element->getSortOrder(),
                        ], updateTimestamp: false);
                    } else {
                        $newElementId = $element->getCanonicalId();
                    }
                } elseif (!$force && $element->getPrimaryOwnerId() === $target->id) {
                    // Only the element ownership was duplicated, so just update its sort order for the target element
                    // (use upsert in case the row doesn’t exist though)
                    Db::upsert(Table::ELEMENTS_OWNERS, [
                        'elementId' => $element->id,
                        'ownerId' => $target->id,
                        'sortOrder' => $element->getSortOrder(),
                    ], [
                        'sortOrder' => $element->getSortOrder(),
                    ], updateTimestamp: false);
                    $newElementId = $element->id;
                } else {
                    $newElementId = $elementsService->duplicateElement($element, $newAttributes)->id;
                }

                $newElementIds[$element->id] = $newElementId;
            }

            // Fire a 'afterDuplicateNestedElements' event
            if ($this->hasEventHandlers(self::EVENT_AFTER_DUPLICATE_NESTED_ELEMENTS)) {
                $this->trigger(self::EVENT_AFTER_DUPLICATE_NESTED_ELEMENTS, new DuplicateNestedElementsEvent([
                    'source' => $source,
                    'target' => $target,
                    'newElementIds' => $newElementIds,
                ]));
            }

            if ($deleteOtherNestedElements) {
                // Delete any nested elements that shouldn't be there anymore
                $this->deleteOtherNestedElements($target, array_values($newElementIds));
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Duplicate elements for other sites as well?
        if ($checkOtherSites && $this->propagationMethod !== PropagationMethod::All) {
            // Find the target's site IDs that *aren't* supported by this site's nested elements
            $targetSiteIds = array_map(
                fn(array $siteInfo) => $siteInfo['siteId'],
                ElementHelper::supportedSitesForElement($target),
            );
            $fieldSiteIds = $this->getSupportedSiteIds($target);
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

                // Duplicate nested elements, ensuring we don't process the same elements more than once
                $handledSiteIds = [];

                foreach ($otherSources as $otherSource) {
                    // Make sure the target actually exists for this site
                    if (!isset($otherTargets[$otherSource->siteId])) {
                        continue;
                    }

                    // Make sure we haven't already duplicated nested elements for this site, via propagation from another site
                    if (in_array($otherSource->siteId, $handledSiteIds)) {
                        continue;
                    }

                    $otherTargets[$otherSource->siteId]->updatingFromDerivative = $target->updatingFromDerivative;
                    $this->duplicateNestedElements($otherSource, $otherTargets[$otherSource->siteId]);

                    // Make sure we don't duplicate nested elements for any of the sites that were just propagated to
                    $sourceSupportedSiteIds = $this->getSupportedSiteIds($otherSource);
                    $handledSiteIds = array_merge($handledSiteIds, $sourceSupportedSiteIds);
                }
            }
        }
    }

    /**
     * Creates revisions for all the nested elements that belong to the given canonical element, and assigns those
     * revisions to the given owner revision.
     *
     * @param ElementInterface $canonical The canonical element
     * @param ElementInterface $revision The revision element
     */
    private function createRevisions(ElementInterface $canonical, ElementInterface $revision): void
    {
        // Only fetch nested elements in the sites the owner element supports
        $siteIds = array_map(
            fn(array $siteInfo) => $siteInfo['siteId'],
            ElementHelper::supportedSitesForElement($canonical),
        );

        /** @var NestedElementInterface[] $elements */
        $elements = $this->nestedElementQuery($canonical)
            ->siteId($siteIds)
            ->preferSites([$canonical->siteId])
            ->unique()
            ->status(null)
            ->all();

        $revisionsService = Craft::$app->getRevisions();
        $elementRevisionIds = [];
        $ownershipData = [];
        $map = [];

        foreach ($elements as $element) {
            $elementRevisionId = $elementRevisionIds[] = $revisionsService->createRevision($element, null, null, [
                'primaryOwnerId' => $revision->id,
                'saveOwnership' => false,
            ]);
            $ownershipData[] = [$elementRevisionId, $revision->id, $element->getSortOrder()];
            $map[$element->id] = $elementRevisionId;
        }

        Db::delete(Table::ELEMENTS_OWNERS, [
            'ownerId' => $revision->id,
            'elementId' => $elementRevisionIds,
        ]);
        Db::batchInsert(Table::ELEMENTS_OWNERS, ['elementId', 'ownerId', 'sortOrder'], $ownershipData);

        // Fire a 'afterDuplicateNestedElements' event
        if (!empty($map) && $this->hasEventHandlers(self::EVENT_AFTER_CREATE_REVISIONS)) {
            $this->trigger(self::EVENT_AFTER_CREATE_REVISIONS, new DuplicateNestedElementsEvent([
                'source' => $canonical,
                'target' => $revision,
                'newElementIds' => $map,
            ]));
        }
    }

    /**
     * Merges recent canonical changes into the nested elements.
     *
     * @param ElementInterface $owner
     */
    private function mergeCanonicalChanges(ElementInterface $owner): void
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

            // Get all the canonical owner’s nested elements, including soft-deleted ones
            /** @var NestedElementInterface[] $canonicalElements */
            $canonicalElements = $this->nestedElementQuery($canonicalOwner)
                ->siteId($canonicalOwner->siteId)
                ->status(null)
                ->trashed(null)
                ->ignorePlaceholders()
                ->all();

            // Get all the derivative owner’s nested elements, so we can compare
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

                    // Has it been soft-deleted?
                    if ($canonicalElement->trashed) {
                        // Delete the derivative element too, unless any changes were made to it
                        if ($derivativeElement->dateUpdated == $derivativeElement->dateCreated) {
                            $elementsService->deleteElement($derivativeElement);
                        }
                    } elseif (!$derivativeElement->trashed && ElementHelper::isOutdated($derivativeElement)) {
                        // Merge the upstream changes into the derivative nested element
                        $elementsService->mergeCanonicalChanges($derivativeElement);
                    }
                } elseif (!$canonicalElement->trashed && $canonicalElement->dateCreated > $owner->dateCreated) {
                    // This is a new element, so duplicate it into the derivative owner
                    $elementsService->duplicateElement($canonicalElement, [
                        'canonicalId' => $canonicalElement->id,
                        'primaryOwner' => $owner,
                        'owner' => $localizedOwners[$canonicalElement->siteId],
                        'siteId' => $canonicalElement->siteId,
                        'propagating' => false,
                    ]);
                }
            }

            // Keep track of the sites we've already covered
            $siteIds = $this->getSupportedSiteIds($canonicalOwner);
            foreach ($siteIds as $siteId) {
                $handledSiteIds[$siteId] = true;
            }
        }
    }

    /**
     * Deletes nested elements alongside the given owner element.
     *
     * @param ElementInterface $owner
     * @param bool $hardDelete Whether the nested elements should be hard-deleted immediately, instead of soft-deleted.
     */
    public function deleteNestedElements(ElementInterface $owner, bool $hardDelete = false): void
    {
        foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
            $elementsService = Craft::$app->getElements();
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
                // If the element is a revision, see if we can reassign it to a new primary owner
                if ($element->getIsRevision() && !isset($element->dateDeleted)) {
                    $newOwnerId = (new Query())
                        ->select(['ownerId'])
                        ->from(Table::ELEMENTS_OWNERS)
                        ->where(['elementId' => $element->id])
                        ->andWhere(['not', ['ownerId' => $owner->id]])
                        ->orderBy(['ownerId' => SORT_ASC])
                        ->scalar();
                    if ($newOwnerId) {
                        $element->setPrimaryOwnerId($newOwnerId);
                        $elementsService->saveElement($element);
                        continue;
                    }
                }

                $element->deletedWithOwner = true;
                $elementsService->deleteElement($element, $hardDelete);
            }
        }
    }

    /**
     * Restores nested elements which had been deleted alongside their owner.
     *
     * @param ElementInterface $owner
     */
    public function restoreNestedElements(ElementInterface $owner): void
    {
        $elementsService = Craft::$app->getElements();

        foreach (ElementHelper::supportedSitesForElement($owner) as $siteInfo) {
            $query = $this->nestedElementQuery($owner)
                ->status(null)
                ->siteId($siteInfo['siteId'])
                ->trashed()
                ->andWhere(['elements.deletedWithOwner' => true]);
            $query->{$this->ownerIdParam} = null;
            $query->{$this->primaryOwnerIdParam} = $owner->id;
            /** @var NestedElementInterface[] $elements */
            $elements = $query->all();
            $elementsService->restoreElements($elements);
        }
    }
}
