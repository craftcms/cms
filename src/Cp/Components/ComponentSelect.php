<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Component\Contracts\Colorable;
use CraftCms\Cms\Component\Contracts\Describable;
use CraftCms\Cms\Component\Contracts\Grippable;
use CraftCms\Cms\Component\Contracts\Iconic;
use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Support\Html;

use function CraftCms\Cms\t;

/**
 * A `<craft-component-select>`: the PHP counterpart to the legacy
 * `_includes/forms/componentSelect` template. Renders the selected
 * components as chips, plus a Choose menu of the available options and an
 * optional Create button. The element boots the behavior client-side (see
 * `resources/js/modules/component-select`).
 *
 *     ComponentSelect::make()
 *         ->name('groups[]')
 *         ->options($groups)
 *         ->values($user->getGroups())
 *         ->showDescription();
 *
 * @since 6.0.0
 */
class ComponentSelect extends ViewComponent
{
    use HasDisabled;
    use HasId;

    protected ?string $name = null;

    protected ?string $inputName = null;

    protected bool $renderDefaultInput = true;

    /** @var list<Chippable> */
    protected array $values = [];

    /** @var list<Chippable>|null */
    protected ?array $options = null;

    protected ?int $limit = null;

    protected bool $showHandles = false;

    protected bool $showIndicators = false;

    protected bool $showDescription = false;

    protected bool $sortable = true;

    protected bool $selectable = true;

    protected bool $showActionMenus = true;

    protected bool $hyperlinks = false;

    protected ?bool $searchable = null;

    protected ?string $createAction = null;

    protected bool $checkboxOptions = false;

    protected bool $inline = false;

    protected function tagName(): string
    {
        return 'craft-component-select';
    }

    /**
     * The input name. A trailing `[]` posts each selected component's ID;
     * an empty value is always posted under the bare name too, unless
     * {@see self::renderDefaultInput()} is turned off.
     */
    public function name(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /** The chips' hidden input name, when it differs from {@see self::name()}. */
    public function inputName(?string $inputName): static
    {
        $this->inputName = $inputName;

        return $this;
    }

    /** Whether to render the empty hidden input that posts when nothing is selected. */
    public function renderDefaultInput(bool $renderDefaultInput = true): static
    {
        $this->renderDefaultInput = $renderDefaultInput;

        return $this;
    }

    /**
     * The selected components.
     *
     * @param  iterable<Chippable>  $values
     */
    public function values(iterable $values): static
    {
        $this->values = array_values([...$values]);

        return $this;
    }

    /**
     * The components that can be chosen.
     *
     * @param  iterable<Chippable>  $options
     */
    public function options(iterable $options): static
    {
        $this->options = array_values([...$options]);

        return $this;
    }

    /** The maximum number of components that can be selected. */
    public function limit(?int $limit): static
    {
        $this->limit = $limit ?: null;

        return $this;
    }

    public function showHandles(bool $showHandles = true): static
    {
        $this->showHandles = $showHandles;

        return $this;
    }

    public function showIndicators(bool $showIndicators = true): static
    {
        $this->showIndicators = $showIndicators;

        return $this;
    }

    public function showDescription(bool $showDescription = true): static
    {
        $this->showDescription = $showDescription;

        return $this;
    }

    /** Whether the chips can be reordered. Ignored when the limit is 1. */
    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    /** Whether chips can be selected for keyboard removal and group dragging. */
    public function selectable(bool $selectable = true): static
    {
        $this->selectable = $selectable;

        return $this;
    }

    public function showActionMenus(bool $showActionMenus = true): static
    {
        $this->showActionMenus = $showActionMenus;

        return $this;
    }

    /** Whether chip labels link to the component's edit page. */
    public function hyperlinks(bool $hyperlinks = true): static
    {
        $this->hyperlinks = $hyperlinks;

        return $this;
    }

    /**
     * Whether the Choose menu has a search input. Defaults to on when there
     * are more than five options.
     */
    public function searchable(?bool $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    /** The CP screen action the Create button opens in a slideout. */
    public function createAction(?string $createAction): static
    {
        $this->createAction = $createAction;

        return $this;
    }

    /**
     * Whether selected components stay listed in the Choose menu as checked
     * checkbox items, instead of being hidden.
     */
    public function checkboxOptions(bool $checkboxOptions = true): static
    {
        $this->checkboxOptions = $checkboxOptions;

        return $this;
    }

    /** Lays the chips out inline rather than stacked. */
    public function inline(bool $inline = true): static
    {
        $this->inline = $inline;

        return $this;
    }

    /**
     * The options, sorted by label and then handle.
     *
     * @return list<Chippable>
     */
    protected function getOptions(): array
    {
        $options = $this->options ?? [];

        usort($options, fn (Chippable $a, Chippable $b): int => [$a->getUiLabel(), $this->handle($a)]
            <=> [$b->getUiLabel(), $this->handle($b)]);

        return $options;
    }

    protected function isSortable(): bool
    {
        return $this->sortable && $this->limit !== 1;
    }

    /**
     * Booleans that default to on are emitted as `"false"` when off, since
     * the element treats a missing attribute as its default.
     */
    #[\Override]
    protected function hostAttributes(): array
    {
        $flag = fn (bool $value): string|bool => $value ?: 'false';

        return [
            'id' => $this->getId() ?? sprintf('componentselect%s', mt_rand()),
            'class' => ['componentselect'],
            'name' => $this->name,
            'limit' => $this->limit,
            'sortable' => $flag($this->isSortable()),
            'selectable' => $flag($this->selectable),
            'show-handles' => $flag($this->showHandles),
            'show-description' => $flag($this->showDescription),
            'show-action-menus' => $flag($this->showActionMenus),
            'hyperlinks' => $flag($this->hyperlinks),
            'checkbox-options' => $flag($this->checkboxOptions),
            'create-action' => $this->createAction,
            'disabled' => $this->isDisabled(),
            'data-id' => 'component-select',
            'slot' => 'input',
        ];
    }

    #[\Override]
    protected function renderMarkup(): string
    {
        $defaultInput = $this->name !== null && $this->renderDefaultInput
            ? (string) Html::hiddenInput(preg_replace('/\[\]$/', '', $this->name), '')
            : '';

        return $defaultInput.parent::renderMarkup();
    }

    #[\Override]
    protected function renderSlots(): string
    {
        return $this->chipsHtml().$this->footerHtml();
    }

    protected function chipsHtml(): string
    {
        $chips = implode('', array_map(
            fn (Chippable $component): string => Html::tag('li', $this->chipHtml($component)),
            $this->values,
        ));

        return Html::tag('ul', $chips, [
            'data-id' => 'component-select-items',
            'class' => array_filter(['components', 'chips', $this->inline ? 'inline-chips' : null]),
        ]);
    }

    protected function chipHtml(Chippable $component): string
    {
        return app(ElementHtml::class)->chipHtml($component, $this->chipConfig($component));
    }

    /**
     * The {@see ElementHtml::chipHtml()} config for a selected component's chip.
     *
     * @return array<string, mixed>
     */
    protected function chipConfig(Chippable $component): array
    {
        return [
            'inputName' => $this->inputName ?? $this->name,
            'showActionMenu' => $this->showActionMenus,
            'showHandle' => $this->showHandles,
            'showIndicators' => $this->showIndicators,
            'showDescription' => $this->showDescription,
            'hyperlink' => $this->hyperlinks,
        ];
    }

    protected function footerHtml(): string
    {
        $html = $this->chooseMenu()->toHtml();

        if ($this->createAction !== null && ! $this->isDisabled()) {
            $html .= FormFields::buttonFromConfig([
                'class' => ['create-btn'],
                'label' => t('Create'),
                'icon' => 'plus',
                'appearance' => 'dashed',
                'command' => '--create-item',
            ])->toHtml();
        }

        return Html::tag('div', $html, [
            'data-id' => 'component-select-footer',
            'class' => ['flex', 'gap-sm', 'pt-2'],
        ]);
    }

    protected function chooseMenu(): ActionMenu
    {
        $options = $this->getOptions();
        $selectedIds = array_map(fn (Chippable $component) => $component->getId(), $this->values);

        return ActionMenu::make()
            ->invoker(Button::make()
                ->type('button')
                ->icon('chevron-down')
                ->command('--choose-item')
                ->variant('dashed')
                ->label(t('Choose'))
                ->disabled($this->isDisabled()))
            ->searchable($this->searchable ?? count($options) > 5)
            ->attributes(['class' => ['me-sm']])
            ->items(array_map(
                fn (Chippable $component): array => $this->optionItem(
                    $component,
                    in_array($component->getId(), $selectedIds),
                ),
                $options,
            ));
    }

    /**
     * A Choose-menu item for an option. `hidden` marks options that are
     * already selected; the menu's search filter uses its own
     * `data-search-hidden` channel and matches against the text content plus
     * `data-keywords`. With checkbox options, selected options stay listed
     * and are checked instead.
     *
     * @return array<string, mixed>
     */
    protected function optionItem(Chippable $component, bool $selected): array
    {
        $handle = $this->showHandles ? $this->handle($component) : null;
        $description = $this->showDescription && $component instanceof Describable
            ? $component->getDescription()
            : null;

        return [
            'label' => $component->getUiLabel(),
            'handle' => $handle ?: null,
            'description' => $description ?: null,
            'keywords' => implode(' ', array_filter([$handle, $description])) ?: null,
            'icon' => $component instanceof Iconic ? ($component->getIcon() ?: null) : null,
            'iconColor' => $component instanceof Colorable ? $component->getColor()?->value : null,
            'hidden' => ! $this->checkboxOptions && $selected,
            'attributes' => [
                'type' => $this->checkboxOptions ? 'checkbox' : false,
                'checked' => $this->checkboxOptions && $selected,
                'data' => [
                    'type' => $component::class,
                    'id' => $component->getId(),
                ],
            ],
        ];
    }

    protected function handle(Chippable $component): ?string
    {
        return $component instanceof Grippable ? $component->getHandle() : null;
    }
}
