<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * Renders the element chips and “Choose” button that
 * `<craft-element-select-input>` (or its entry and asset variants) boots into
 * an element selector.
 *
 * @since 6.0.0
 */
class ElementSelect extends ViewComponent
{
    use HasDisabled;
    use HasId;

    private const array DEFAULT_INPUT_CLASSES = [
        'Craft.BaseElementSelectInput',
        'Craft.EntrySelectInput',
        'Craft.AssetSelectInput',
    ];

    protected ?string $name = null;

    /** @var class-string<ElementInterface>|null */
    protected ?string $elementType = null;

    /** @var ElementInterface[] */
    protected array $elements = [];

    protected ?string $inputClass = null;

    protected string $context = 'field';

    /** @var array<array-key, string|null>|string|null */
    protected array|string|null $sources = null;

    protected ?ElementConditionInterface $condition = null;

    /** @var array<string, mixed>|null */
    protected ?array $criteria = null;

    /** @var array<string, mixed>|null */
    protected ?array $searchCriteria = null;

    protected ?ElementInterface $referenceElement = null;

    protected ?int $sourceElementId = null;

    protected ?string $storageKey = null;

    protected string $defaultPlacement = 'end';

    protected string $viewMode = 'list';

    protected bool $prevalidate = false;

    protected ?int $fieldId = null;

    protected bool $single = false;

    protected ?int $limit = null;

    protected bool $showActionMenu = true;

    protected bool $sortable = true;

    protected bool $maintainHierarchy = false;

    protected ?int $branchLimit = null;

    protected bool $allowAdd = true;

    protected bool $allowRemove = true;

    protected bool $allowSelfRelations = false;

    /** @var list<int|null>|null */
    protected ?array $disabledElementIds = null;

    protected bool|string $showSiteMenu = false;

    /** @var list<int>|null */
    protected ?array $siteIds = null;

    protected ?string $selectionLabel = null;

    /** @var array<string, mixed> */
    protected array $modalSettings = [];

    /** @var array<string, mixed> */
    protected array $settings = [];

    protected ?string $describedBy = null;

    protected ?string $labelId = null;

    protected ?string $size = null;

    protected ?bool $inline = null;

    protected ?bool $showInGrid = null;

    protected function tagName(): string
    {
        return match (true) {
            is_a((string) $this->elementType, Asset::class, true) => 'craft-asset-select-input',
            is_a((string) $this->elementType, Entry::class, true) => 'craft-entry-select-input',
            default => 'craft-element-select-input',
        };
    }

    public function name(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /** @param class-string<ElementInterface>|null $elementType */
    public function elementType(?string $elementType): static
    {
        $this->elementType = $elementType;

        return $this;
    }

    /** @param ElementInterface[]|null $elements */
    public function elements(?array $elements): static
    {
        $this->elements = array_values($elements ?? []);

        return $this;
    }

    /** A global JS class name, such as `Craft.CategorySelectInput`, to boot instead of the element type’s default. */
    public function inputClass(?string $inputClass): static
    {
        $this->inputClass = $inputClass;

        return $this;
    }

    public function context(string $context): static
    {
        $this->context = $context;

        return $this;
    }

    /** @param array<array-key, string|null>|string|null $sources */
    public function sources(array|string|null $sources): static
    {
        $this->sources = $sources;

        return $this;
    }

    public function condition(?ElementConditionInterface $condition): static
    {
        $this->condition = $condition;

        return $this;
    }

    /** @param array<string, mixed>|null $criteria */
    public function criteria(?array $criteria): static
    {
        $this->criteria = $criteria;

        return $this;
    }

    /**
     * Shows a search input that filters elements matching these criteria.
     *
     * @param  array<string, mixed>|null  $searchCriteria
     */
    public function searchCriteria(?array $searchCriteria): static
    {
        $this->searchCriteria = $searchCriteria;

        return $this;
    }

    public function referenceElement(?ElementInterface $referenceElement): static
    {
        $this->referenceElement = $referenceElement;

        return $this;
    }

    public function sourceElementId(?int $sourceElementId): static
    {
        $this->sourceElementId = $sourceElementId;

        return $this;
    }

    /** Key the selector modal remembers its source and view state under. */
    public function storageKey(?string $storageKey): static
    {
        $this->storageKey = $storageKey;

        return $this;
    }

    /** @param 'beginning'|'end' $defaultPlacement */
    public function defaultPlacement(string $defaultPlacement): static
    {
        $this->defaultPlacement = $defaultPlacement;

        return $this;
    }

    public function viewMode(string $viewMode): static
    {
        $this->viewMode = $viewMode;

        return $this;
    }

    public function prevalidate(bool $prevalidate = true): static
    {
        $this->prevalidate = $prevalidate;

        return $this;
    }

    public function fieldId(?int $fieldId): static
    {
        $this->fieldId = $fieldId;

        return $this;
    }

    public function single(bool $single = true): static
    {
        $this->single = $single;

        return $this;
    }

    public function limit(?int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function showActionMenu(bool $showActionMenu = true): static
    {
        $this->showActionMenu = $showActionMenu;

        return $this;
    }

    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function maintainHierarchy(bool $maintainHierarchy = true): static
    {
        $this->maintainHierarchy = $maintainHierarchy;

        return $this;
    }

    public function branchLimit(?int $branchLimit): static
    {
        $this->branchLimit = $branchLimit;

        return $this;
    }

    public function allowAdd(bool $allowAdd = true): static
    {
        $this->allowAdd = $allowAdd;

        return $this;
    }

    public function allowRemove(bool $allowRemove = true): static
    {
        $this->allowRemove = $allowRemove;

        return $this;
    }

    public function allowSelfRelations(bool $allowSelfRelations = true): static
    {
        $this->allowSelfRelations = $allowSelfRelations;

        return $this;
    }

    /** @param list<int|null>|null $disabledElementIds */
    public function disabledElementIds(?array $disabledElementIds): static
    {
        $this->disabledElementIds = $disabledElementIds;

        return $this;
    }

    public function showSiteMenu(bool|string $showSiteMenu = true): static
    {
        $this->showSiteMenu = $showSiteMenu;

        return $this;
    }

    /** @param list<int>|null $siteIds */
    public function siteIds(?array $siteIds): static
    {
        $this->siteIds = $siteIds;

        return $this;
    }

    /** The button label, and the selector modal’s title. Defaults to “Choose”. */
    public function selectionLabel(?string $selectionLabel): static
    {
        $this->selectionLabel = $selectionLabel;

        return $this;
    }

    /** @param array<string, mixed> $modalSettings */
    public function modalSettings(array $modalSettings): static
    {
        $this->modalSettings = Arr::merge($this->modalSettings, $modalSettings);

        return $this;
    }

    /**
     * Settings merged over the computed ones before they reach the JS class.
     *
     * @param  array<string, mixed>  $settings
     */
    public function settings(array $settings): static
    {
        $this->settings = [...$this->settings, ...$settings];

        return $this;
    }

    public function describedBy(?string $describedBy): static
    {
        $this->describedBy = $describedBy;

        return $this;
    }

    /** The id of the field label, which names the search input. */
    public function labelId(?string $labelId): static
    {
        $this->labelId = $labelId;

        return $this;
    }

    /** @param 'small'|'large'|null $size Chip size. Defaults by view mode. */
    public function size(?string $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function inline(?bool $inline = true): static
    {
        $this->inline = $inline;

        return $this;
    }

    public function showInGrid(?bool $showInGrid = true): static
    {
        $this->showInGrid = $showInGrid;

        return $this;
    }

    #[\Override]
    protected function renderedAttributes(): array
    {
        $attributes = parent::renderedAttributes();
        $attributes['class'] = array_values(array_unique(['elementselect', ...Html::explodeClass($attributes['class'] ?? [])]));

        return $attributes;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        $inputClass = in_array($this->inputClass, self::DEFAULT_INPUT_CLASSES, true) ? null : $this->inputClass;

        return [
            'id' => $this->getId(),
            'input-class' => $inputClass,
            'settings' => Json::encode($this->jsSettings()),
        ];
    }

    #[\Override]
    protected function renderSlots(): string
    {
        $html = $this->name !== null ? Html::hiddenInput($this->name, '') : '';
        $html .= $this->elementsHtml();

        $limit = $this->effectiveLimit();
        $buttons = '';

        if ($this->allowAdd) {
            $buttons .= Html::tag('craft-button', Html::encode($this->selectionLabel ?? t('Choose')), [
                'type' => 'button',
                'command' => '--add-element',
                'variant' => 'dashed',
                'icon' => 'plus',
                'class' => array_filter([
                    'add',
                    $limit && count($this->elements) >= $limit ? 'hidden' : null,
                ]),
                'disabled' => $this->isDisabled(),
                'aria' => ['describedby' => $this->describedBy ?: false],
            ]);

            if ($this->searchCriteria) {
                $buttons .= Html::tag('div', Input::make()
                    ->width('auto')
                    ->placeholder(t('Search'))
                    ->describedBy($this->describedBy ?: null)
                    ->role('combobox')
                    ->labelledBy($this->labelId ?: null)
                    ->toHtml().
                    Html::tag('craft-spinner', '', ['class' => ['spinner', 'hidden']]), [
                        'class' => ['texticon', 'search', 'icon', 'elementselect__search-input-wrapper'],
                    ]);
            }
        }

        $buttons .= Html::tag('craft-spinner', '', ['class' => ['spinner', 'hidden']]);

        return $html.Html::tag('div', $buttons, ['class' => 'flex']).parent::renderSlots();
    }

    private function effectiveLimit(): ?int
    {
        return $this->single ? 1 : $this->limit;
    }

    private function elementsHtml(): string
    {
        $variables = [
            'elements' => $this->elements,
            'context' => $this->context,
            'disabled' => $this->isDisabled(),
            'name' => $this->name,
            'single' => $this->single,
            'showActionMenu' => $this->showActionMenu,
            'sortable' => false,
        ];

        if ($this->maintainHierarchy) {
            return template('_elements/structurelist.twig', [...$variables, 'id' => $this->getId()]);
        }

        if (in_array($this->viewMode, ['cards', 'cards-grid'], true)) {
            return template('_elements/cards.twig', [
                ...$variables,
                'showInGrid' => $this->showInGrid ?? $this->viewMode === 'cards-grid',
            ]);
        }

        return template('_elements/list.twig', [
            ...$variables,
            'size' => $this->size ?? (in_array($this->viewMode, ['thumbs', 'large'], true) ? 'large' : 'small'),
            'inline' => $this->inline ?? in_array($this->viewMode, ['list-inline', 'thumbs', 'large'], true),
        ]);
    }

    /** @return array<string, mixed> */
    private function jsSettings(): array
    {
        $limit = $this->effectiveLimit();
        $reference = $this->referenceElement;

        return [
            'id' => $this->getId() !== null ? InputNamespace::namespaceId($this->getId()) : null,
            'name' => $this->name !== null ? InputNamespace::namespaceInputName($this->name) : null,
            'elementType' => $this->elementType,
            'context' => $this->context,
            'sources' => $this->sources,
            'condition' => $this->condition?->getConfig(),
            'referenceElementId' => $reference?->id,
            'referenceElementOwnerId' => $reference instanceof NestedElementInterface ? $reference->getOwnerId() : null,
            'referenceElementSiteId' => $reference?->siteId,
            'criteria' => $this->criteria,
            'searchCriteria' => $this->searchCriteria,
            'allowAdd' => $this->allowAdd,
            'allowRemove' => $this->allowRemove,
            'allowSelfRelations' => $this->allowSelfRelations,
            'maintainHierarchy' => $this->maintainHierarchy,
            'branchLimit' => $this->branchLimit,
            'sourceElementId' => $this->sourceElementId,
            'disabledElementIds' => $this->disabledElementIds,
            'defaultPlacement' => $this->defaultPlacement,
            'viewMode' => $this->viewMode,
            'single' => $this->single,
            'limit' => $limit,
            'showSiteMenu' => $this->showSiteMenu,
            'siteIds' => $this->siteIds,
            'modalStorageKey' => $this->storageKey,
            'fieldId' => $this->fieldId,
            'sortable' => (! $limit || $limit > 1) && $this->sortable,
            'selectable' => ! $this->isDisabled(),
            'showActionMenu' => $this->showActionMenu,
            'editable' => ! $this->isDisabled(),
            'prevalidate' => $this->prevalidate,
            'modalSettings' => Arr::merge(
                ['modalTitle' => $this->selectionLabel ?? t('Choose')],
                $this->modalSettings,
            ),
            ...$this->settings,
        ];
    }
}
