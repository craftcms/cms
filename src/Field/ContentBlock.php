<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\FieldLayoutProviderInterface;
use craft\base\NestedElementInterface;
use craft\behaviors\EventBehavior;
use craft\elements\ContentBlock as ContentBlockElement;
use craft\elements\db\ContentBlockQuery;
use craft\elements\db\EagerLoadPlan;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\ElementCollection;
use craft\elements\NestedElementManager;
use craft\errors\InvalidFieldException;
use craft\events\CancelableEvent;
use craft\gql\resolvers\elements\ContentBlock as ContentBlockResolver;
use craft\gql\types\generators\ContentBlock as ContentBlockGenerator;
use craft\gql\types\input\ContentBlock as ContentBlockInputType;
use craft\helpers\Gql;
use craft\models\FieldLayout;
use craft\web\assets\cp\CpAsset;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json as JsonHelper;
use CraftCms\Cms\User\Elements\User;
use DateTime;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;
use Override;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

use function CraftCms\Cms\t;

/**
 * Content Block field type
 */
final class ContentBlock extends Field implements ElementContainerFieldInterface, FieldLayoutProviderInterface
{
    private const string VIEW_MODE_GROUPED = 'grouped';

    private const string VIEW_MODE_PANE = 'pane';

    private const string VIEW_MODE_INLINE = 'inline';

    /** {@inheritdoc} */
    #[Override]
    public static function displayName(): string
    {
        return t('Content Block');
    }

    /** {@inheritdoc} */
    #[Override]
    public static function icon(): string
    {
        return 'block';
    }

    /** {@inheritdoc} */
    #[Override]
    public static function supportedTranslationMethods(): array
    {
        // Don't ever automatically propagate values to other sites.
        return [
            self::TRANSLATION_METHOD_SITE,
        ];
    }

    /** {@inheritdoc} */
    #[Override]
    public static function phpType(): string
    {
        return sprintf('\\%s|null', ContentBlockElement::class);
    }

    /** {@inheritdoc} */
    #[Override]
    public static function dbType(): array|string|null
    {
        return null;
    }

    /**
     * @var string The field’s view mode
     *
     * @phpstan-var self::VIEW_MODE_*
     */
    public string $viewMode = self::VIEW_MODE_GROUPED;

    private FieldLayout $_fieldLayout;

    /**
     * @see contentBlockManager()
     */
    private NestedElementManager $_contentBlockManager;

    private function contentBlockManager(): NestedElementManager
    {
        $this->_contentBlockManager ??= new NestedElementManager(
            ContentBlockElement::class,
            fn (ElementInterface $owner) => $this->createContentBlockQuery($owner),
            [
                'field' => $this,
                'criteria' => [
                    'fieldId' => $this->id,
                ],
                'propagationMethod' => PropagationMethod::All,
                'valueGetter' => fn (ElementInterface $owner) => ElementCollection::make([
                    $owner->getFieldValue($this->handle),
                ]),
            ],
        );

        return $this->_contentBlockManager;
    }

    /** {@inheritdoc} */
    public function getSettings(): array
    {
        $fieldLayout = $this->getFieldLayout();

        return [
            ...parent::getSettings(),
            'fieldLayouts' => [
                $fieldLayout->uid => $fieldLayout->getConfig(),
            ],
        ];
    }

    public function afterValidate(Validator $validator): void
    {
        $fieldLayout = $this->getFieldLayout();
        $fieldLayout->validate();

        if (! $this->ensureNoRecursion($this)) {
            $validator->errors()->add('fieldLayout', t('Including a Content Block field recursively is not allowed.'));
        }
    }

    private function ensureNoRecursion(self $field): bool
    {
        return array_all($field->getFieldLayout()->getCustomFields(), fn ($customField) => ! ($customField instanceof self && ($customField->id === $this->id || ! $this->ensureNoRecursion($customField))));
    }

    /** {@inheritdoc} */
    public function getFieldLayoutProviders(): array
    {
        return [$this];
    }

    /** {@inheritdoc} */
    public function getUriFormatForElement(NestedElementInterface $element): ?string
    {
        return null;
    }

    /** {@inheritdoc} */
    public function getRouteForElement(NestedElementInterface $element): mixed
    {
        return null;
    }

    /** {@inheritdoc} */
    public function getFieldLayout(): FieldLayout
    {
        $this->_fieldLayout ??= new FieldLayout([
            'type' => ContentBlockElement::class,
            'provider' => $this,
        ]);

        return $this->_fieldLayout;
    }

    /**
     * Sets the field layout.
     */
    public function setFieldLayout(FieldLayout|array|string $layout): void
    {
        if (is_string($layout)) {
            $layout = JsonHelper::decode($layout);
        }

        if (is_array($layout)) {
            $layout = Fields::createLayout($layout);
            $layout->type = ContentBlockElement::class;

            // Make sure all the elements have a dateAdded value set
            foreach ($layout->getTabs() as $tab) {
                foreach ($tab->getElements() as $layoutElement) {
                    $layoutElement->dateAdded ??= new DateTime;
                }
            }
        }

        $layout->provider = $this;
        $this->_fieldLayout = $layout;
    }

    /**
     * Sets the field layouts.
     */
    public function setFieldLayouts(array $layouts): void
    {
        $config = reset($layouts);
        $layout = Fields::createLayout($config ?: []);
        $layout->uid = array_key_first($layouts);
        $layout->type = ContentBlockElement::class;

        // Make sure all the elements have a dateAdded value set
        foreach ($layout->getTabs() as $tab) {
            foreach ($tab->getElements() as $layoutElement) {
                $layoutElement->dateAdded ??= new DateTime;
            }
        }

        $this->setFieldLayout($layout);
    }

    /**
     * Sets the generated fields on the field layout.
     */
    public function setGeneratedFields(mixed $fields): void
    {
        if (! is_array($fields)) {
            $fields = null;
        }

        $this->getFieldLayout()->setGeneratedFields($fields);
    }

    /**
     * Returns the field layout’s UUID.
     */
    public function getFieldLayoutUid(): string
    {
        return $this->getFieldLayout()->uid;
    }

    /**
     * Sets the field layout based on its UUID.
     */
    public function setFieldLayoutUid(string $uid): void
    {
        $layout = Fields::getLayoutByUid($uid);
        if (! $layout) {
            throw new InvalidArgumentException("Invalid field layout UUID: $uid");
        }
        $layout->provider = $this;
        $this->_fieldLayout = $layout;
    }

    /** {@inheritdoc} */
    public function getSupportedSitesForElement(NestedElementInterface $element): array
    {
        try {
            $owner = $element->getOwner();
        } catch (InvalidConfigException) {
            $owner = $element->duplicateOf;
        }

        if (! $owner) {
            return [Sites::getPrimarySite()->id];
        }

        return $this->contentBlockManager()->getSupportedSiteIds($owner);
    }

    /** {@inheritdoc} */
    public function canViewElement(NestedElementInterface $element, User $user): bool
    {
        $owner = $element->getOwner();

        return $owner && Craft::$app->getElements()->canView($owner, $user);
    }

    /** {@inheritdoc} */
    public function canSaveElement(NestedElementInterface $element, User $user): bool
    {
        $owner = $element->getOwner();

        if (! $owner) {
            return false;
        }

        if (Craft::$app->getElements()->canSave($owner, $user)) {
            return true;
        }

        // Check all the owners. Maybe the user can save one of the other ones?
        /** @phpstan-ignore-next-line  */
        if (! Craft::$app->getElements()->canSave($owner, $user) && ! $owner->getIsRevision()) {
            foreach ($element->getOwners(['revisions' => false]) as $o) {
                if ($o->id !== $owner->id && Craft::$app->getElements()->canSave($o, $user)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** {@inheritdoc} */
    public function canDuplicateElement(NestedElementInterface $element, User $user): bool
    {
        return false;
    }

    /** {@inheritdoc} */
    public function canDeleteElement(NestedElementInterface $element, User $user): bool
    {
        return false;
    }

    /** {@inheritdoc} */
    public function canDeleteElementForSite(NestedElementInterface $element, User $user): bool
    {
        return false;
    }

    /** {@inheritdoc} */
    public function getSettingsHtml(): string
    {
        return $this->settingsHtml(false);
    }

    /** {@inheritdoc} */
    public function getReadOnlySettingsHtml(): string
    {
        return $this->settingsHtml(true);
    }

    private function settingsHtml(bool $readOnly): string
    {
        $bundle = Craft::$app->getView()->registerAssetBundle(CpAsset::class);

        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/ContentBlock/settings.twig', [
            'field' => $this,
            'readOnly' => $readOnly,
            'baseIconsUrl' => "$bundle->baseUrl/images/content-block",
        ]);
    }

    /** {@inheritdoc} */
    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->_normalizeValueInternal($value, $element, false);
    }

    /** {@inheritdoc} */
    #[Override]
    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->_normalizeValueInternal($value, $element, true);
    }

    private function _normalizeValueInternal(
        mixed $value,
        ?ElementInterface $element,
        bool $fromRequest,
    ): ContentBlockElement {
        if ($value instanceof ElementQueryInterface) {
            /** @var ?ContentBlockElement $contentBlock */
            $contentBlock = $value->one();

            if ($contentBlock) {
                $this->setOwnerOnContentBlockElement($element, $contentBlock);

                return $contentBlock;
            }

            return $this->createContentBlockElement($element);
        }

        if ($value === '') {
            return $this->createContentBlockElement($element);
        }

        // Set the initially matched elements if $value is already set, which is the case if there was a validation
        // error or we're loading a revision.
        if ($value === '*') {
            $contentBlock = $this->createContentBlockQuery($element)
                ->drafts(null)
                ->savedDraftsOnly()
                ->status(null)
                ->one();

            if ($contentBlock) {
                $this->setOwnerOnContentBlockElement($element, $contentBlock);

                return $contentBlock;
            }

            return $this->createContentBlockElement($element);
        }

        if ($element && is_array($value)) {
            return $this->_createContentBlockFromSerializedData($value, $element, $fromRequest);
        }

        if (Craft::$app->getRequest()->getIsPreview()) {
            $contentBlock = $this->createContentBlockQuery($element)
                ->withProvisionalDrafts()
                ->one();

            if ($contentBlock) {
                $this->setOwnerOnContentBlockElement($element, $contentBlock);

                return $contentBlock;
            }

            return $this->createContentBlockElement($element);
        }

        $handle = sprintf('content-block:%s', $this->layoutElement?->getOriginalHandle() ?? $this->handle);
        if ($element->hasEagerLoadedElements($handle)) {
            /** @phpstan-ignore-next-line */
            return $element->getEagerLoadedElements($handle)->first() ?? $this->createContentBlockElement($element);
        }

        if (isset($element->elementQueryResult) && count($element->elementQueryResult) > 1) {
            /** @var ElementInterface[] $sameSiteElements */
            $sameSiteElements = Collection::make($element->elementQueryResult)
                ->filter(fn (ElementInterface $e) => $e->siteId === $element->siteId)
                ->all();

            if (count($sameSiteElements) > 1) {
                $contentBlocks = ContentBlockElement::find()
                    ->fieldId($this->id)
                    ->ownerId(array_map(fn (ElementInterface $e) => $e->id, $sameSiteElements))
                    ->siteId($element->siteId)
                    ->indexBy('ownerId')
                    ->collect();

                foreach ($sameSiteElements as $e) {
                    $contentBlock = $contentBlocks[$e->id] ?? null;
                    if ($contentBlock) {
                        $this->setOwnerOnContentBlockElement($e, $contentBlock);
                    }
                    $e->setEagerLoadedElements($handle, $contentBlock ? [$contentBlock] : [], new EagerLoadPlan([
                        'handle' => $handle,
                    ]));
                }

                /** @phpstan-ignore-next-line */
                return $element->getEagerLoadedElements($handle)?->first() ?? $this->createContentBlockElement($element);
            }
        }

        $contentBlock = $this->createContentBlockQuery($element)->one();

        if ($contentBlock) {
            $this->setOwnerOnContentBlockElement($element, $contentBlock);

            return $contentBlock;
        }

        return $this->createContentBlockElement($element);
    }

    private function createContentBlockElement(?ElementInterface $owner): ContentBlockElement
    {
        return Craft::$app->getElements()->createElement([
            'type' => ContentBlockElement::class,
            'siteId' => $owner->siteId,
            'owner' => $owner,
            'fieldId' => $this->id,
        ]);
    }

    private function createContentBlockQuery(?ElementInterface $owner): ContentBlockQuery
    {
        $query = ContentBlockElement::find();

        // Existing element?
        if ($owner?->id) {
            $query->attachBehavior(self::class, new EventBehavior([
                ElementQuery::EVENT_BEFORE_PREPARE => function (
                    CancelableEvent $event,
                    ContentBlockQuery $query,
                ) use ($owner) {
                    $query->ownerId = $owner->id;

                    // Clear out id=false if this query was populated previously
                    if ($query->id === false) {
                        $query->id = null;
                    }

                    // If the owner is a revision, allow revision elements to be returned as well
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

    private function setOwnerOnContentBlockElement(ElementInterface $owner, ContentBlockElement $contentBlock): void
    {
        $contentBlock->setOwner($owner);
        if ($owner->id === $contentBlock->getPrimaryOwnerId()) {
            $contentBlock->setPrimaryOwner($owner);
        }
    }

    /** {@inheritdoc} */
    #[Override]
    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        /** @var ContentBlockElement $value */
        if (! $value->id) {
            return null;
        }

        return [
            'fields' => $value->getSerializedFieldValues(),
        ];
    }

    /** {@inheritdoc} */
    #[Override]
    public function serializeValueForDb(mixed $value, ElementInterface $element): mixed
    {
        /** @var ContentBlockElement $value */
        if (! $value->id) {
            return null;
        }

        return [
            'fields' => $value->getSerializedFieldValuesForDb(),
        ];
    }

    /** {@inheritdoc} */
    #[Override]
    public function copyValue(ElementInterface $from, ElementInterface $to): void
    {
        // We'll do it later from afterElementPropagate()
    }

    /** {@inheritdoc} */
    #[Override]
    public function getIsTranslatable(?ElementInterface $element): bool
    {
        return $this->contentBlockManager()->getIsTranslatable($element);
    }

    /** {@inheritdoc} */
    #[Override]
    public function showStatus(): bool
    {
        return false;
    }

    /** {@inheritdoc} */
    #[Override]
    public function useFieldset(): bool
    {
        return $this->viewMode !== self::VIEW_MODE_INLINE;
    }

    /** {@inheritdoc} */
    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        if (! $element?->id) {
            $message = t('{nestedType} can only be created after the {ownerType} has been saved.', [
                'nestedType' => ContentBlockElement::pluralDisplayName(),
                'ownerType' => $element ? $element::lowerDisplayName() : t('element'),
            ]);

            return Html::tag('div', $message, ['class' => 'pane no-border zilch small']);
        }

        return $this->inputHtmlInternal($value, $element, false);
    }

    /** {@inheritdoc} */
    #[Override]
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return $this->inputHtmlInternal($value, $element, true);
    }

    private function inputHtmlInternal(mixed $value, ?ElementInterface $element, bool $static): string
    {
        // Make sure the content block is fully saved
        /** @var ContentBlockElement $value */
        if (! $value->id) {
            Craft::$app->getElements()->saveElement($value);
        }

        $view = Craft::$app->getView();
        $id = $this->getInputId();

        $originalNamespace = $view->getNamespace();
        $namespace = $view->namespaceInputName($this->handle);
        $view->setNamespace($namespace);
        $form = $this->getFieldLayout()->createForm($value, $static);
        $view->setNamespace($originalNamespace);

        $formHtml = $view->namespaceInputs(fn () => $form->render(), $this->handle);

        $settings = [
            'baseInputName' => $namespace,
            'ownerElementType' => $element::class,
            'ownerId' => $element->id,
            'fieldId' => $this->id,
            'siteId' => $element->siteId,
            'elementId' => $value->id,
            'visibleLayoutElements' => $form->getVisibleElements(),
        ];

        $view->registerJsWithVars(fn ($id, $settings) => <<<JS
(() => {
  new Craft.ContentBlockEditor($('#' + $id), $settings)
})();
JS, [
            $view->namespaceInputId($id),
            $settings,
        ]);

        return Html::tag('div', $formHtml, [
            'id' => $id,
            'class' => match ($this->viewMode) {
                self::VIEW_MODE_GROUPED => ['pane', 'hairline'],
                self::VIEW_MODE_PANE => ['pane'],
                default => null,
            },
            'style' => match ($this->viewMode) {
                self::VIEW_MODE_GROUPED, self::VIEW_MODE_PANE => [
                    '--pane-padding' => 'var(--m)',
                    '--padding' => 'var(--m)',
                    '--neg-padding' => 'calc(var(--m) * -1)',
                    '--row-gap' => 'var(--m)',
                ],
                default => null,
            },
        ]);
    }

    /** {@inheritdoc} */
    #[Override]
    public function getElementValidationRules(): array
    {
        return [
            [
                $this->validateContentBlock(...),
                'on' => [Element::SCENARIO_ESSENTIALS, Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE],
                'skipOnEmpty' => false,
            ],
        ];
    }

    private function validateContentBlock(ElementInterface $element): void
    {
        /** @var ContentBlockElement $value */
        $value = $element->getFieldValue($this->handle);
        $scenario = $element->getScenario();
        $value->setOwner($element);

        if (in_array($scenario, [Element::SCENARIO_ESSENTIALS, Element::SCENARIO_LIVE])) {
            $value->setScenario($scenario);
        }

        if (! $value->validate()) {
            $element->addModelErrors($value, $this->handle);
            if ($value->id) {
                $element->addInvalidNestedElementIds([$value->id]);
            }
        }
    }

    /** {@inheritdoc} */
    #[Override]
    protected function searchKeywords(mixed $value, ElementInterface $element): string
    {
        return $this->contentBlockManager()->getSearchKeywords($element);
    }

    /** {@inheritdoc} */
    //    public function getEagerLoadingMap(array $sourceElements): array|null|false
    //    {
    //        // Get the source element IDs
    //        $sourceElementIds = array_map(fn(elementInterface $element) => $element->id, $sourceElements);
    //
    //        // Return any relation data on these elements, defined with this field
    //        $map = (new Query())
    //            ->select([
    //                'source' => 'elements_owners.ownerId',
    //                'target' => 'contentblocks.id',
    //            ])
    //            ->from(['contentblocks' => DbTable::CONTENTBLOCKS])
    //            ->innerJoin(['elements_owners' => DbTable::ELEMENTS_OWNERS], [
    //                'and',
    //                '[[elements_owners.elementId]] = [[contentblocks.id]]',
    //                ['elements_owners.ownerId' => $sourceElementIds],
    //            ])
    //            ->where(['contentblocks.fieldId' => $this->id])
    //            ->orderBy(['elements_owners.sortOrder' => SORT_ASC])
    //            ->all();
    //
    //        return [
    //            'elementType' => ContentBlockElement::class,
    //            'map' => $map,
    //            'criteria' => [
    //                'fieldId' => $this->id,
    //                'allowOwnerDrafts' => true,
    //                'allowOwnerRevisions' => true,
    //            ],
    //        ];
    //    }

    /** {@inheritdoc} */
    #[Override]
    public function getContentGqlType(): array
    {
        return [
            'name' => $this->handle,
            'type' => ContentBlockGenerator::generateType($this),
            'resolve' => ContentBlockResolver::class.'::resolve',
            'complexity' => Gql::eagerLoadComplexity(),
        ];
    }

    /** {@inheritdoc} */
    #[Override]
    public function getContentGqlMutationArgumentType(): Type|array
    {
        return ContentBlockInputType::getType($this);
    }

    // Events
    // -------------------------------------------------------------------------

    /** {@inheritdoc} */
    #[Override]
    public function afterSave(bool $isNew): void
    {
        Fields::saveLayout($this->getFieldLayout(), false);
        parent::afterSave($isNew);
    }

    /** {@inheritdoc} */
    #[Override]
    public function afterElementPropagate(ElementInterface $element, bool $isNew): void
    {
        $this->contentBlockManager()->maintainNestedElements($element, $isNew);
        parent::afterElementPropagate($element, $isNew);
    }

    /** {@inheritdoc} */
    #[Override]
    public function beforeElementDelete(ElementInterface $element): bool
    {
        if (! parent::beforeElementDelete($element)) {
            return false;
        }

        // Delete any entries that primarily belong to this element
        $this->contentBlockManager()->deleteNestedElements($element, $element->hardDelete);

        return true;
    }

    /** {@inheritdoc} */
    #[Override]
    public function beforeElementDeleteForSite(ElementInterface $element): bool
    {
        $elementsService = Craft::$app->getElements();

        /** @var ContentBlockElement[] $contentBlocks */
        $contentBlocks = ContentBlockElement::find()
            ->primaryOwnerId($element->id)
            ->status(null)
            ->siteId($element->siteId)
            ->all();

        foreach ($contentBlocks as $contentBlock) {
            $elementsService->deleteElementForSite($contentBlock);
        }

        return true;
    }

    /** {@inheritdoc} */
    #[Override]
    public function afterElementRestore(ElementInterface $element): void
    {
        // Also restore any entries for this element
        $this->contentBlockManager()->restoreNestedElements($element);

        parent::afterElementRestore($element);
    }

    /**
     * Creates an array of entries based on the given serialized data.
     *
     * @param  array  $value  The raw field value
     * @param  ElementInterface  $element  The element the field is associated with
     * @param  bool  $fromRequest  Whether the data came from the request post data
     */
    private function _createContentBlockFromSerializedData(
        array $value,
        ElementInterface $element,
        bool $fromRequest,
    ): ContentBlockElement {
        // Get the old content block
        if ($element->id) {
            /** @var ContentBlockElement $contentBlock */
            $contentBlock = $this->createContentBlockQuery($element)->one();
        }

        $request = Craft::$app->getRequest();

        $fieldNamespace = $element->getFieldParamNamespace();
        $baseFieldNamespace = $fieldNamespace ? "$fieldNamespace.$this->handle" : null;

        // Existing content block?
        if (isset($contentBlock)) {
            // Is this a derivative element, and does the content block primarily belong to the canonical?
            if (
                $element->getIsDerivative() &&
                $contentBlock->getPrimaryOwnerId() === $element->getCanonicalId() &&
                // this is so that extra drafts don't get created for matrix in matrix scenario
                // where both are set to inline-editable blocks view mode
                (
                    $request->getIsConsoleRequest() ||
                    $request->getActionSegments() !== ['elements', 'update-field-layout']
                )
            ) {
                // Duplicate it as a draft. (We'll drop its draft status from NestedElementManager::saveNestedElements().)
                $contentBlock = app(Drafts::class)->createDraft($contentBlock, Craft::$app->getUser()->getId(), null, null, [
                    'canonicalId' => $contentBlock->id,
                    'primaryOwnerId' => $element->id,
                    'owner' => $element,
                    'siteId' => $element->siteId,
                    'propagating' => false,
                    'markAsSaved' => false,
                ]);
            }

            $contentBlock->forceSave = true;
        } else {
            $contentBlock = $this->createContentBlockElement($element);
        }

        // Set the content post location on the content block if we can
        if ($baseFieldNamespace) {
            $contentBlock->setFieldParamNamespace("$baseFieldNamespace.fields");
        }

        // if the owner is fresh, ensure the content block's content gets propagated to all sites
        if ($element->getIsFresh()) {
            $contentBlock->propagateAll = true;
        }

        if (isset($value['fields'])) {
            foreach ($value['fields'] as $fieldHandle => $fieldValue) {
                try {
                    if ($fromRequest) {
                        $contentBlock->setFieldValueFromRequest($fieldHandle, $fieldValue);
                    } else {
                        $contentBlock->setFieldValue($fieldHandle, $fieldValue);
                    }
                } catch (InvalidFieldException) {
                }
            }
        }

        return $contentBlock;
    }
}
