<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Concerns\HasSize;
use CraftCms\Cms\Cp\Enums\TabsLayout;
use CraftCms\Cms\Cp\Enums\TabsPlacement;
use CraftCms\Cms\Support\Html;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;
use Stringable;

/**
 * PHP counterpart to the `<craft-tabs>` web component — a strip of tabs over a
 * set of panels, one visible at a time:
 *
 *     Tabs::make()
 *         ->tab(t('Content'), $contentHtml)
 *         ->tab(t('Settings'), $settingsHtml)
 *         ->selectedIndex(1);
 *
 * or from a config array (Twig `ui()`):
 *
 *     {{ ui('tabs', {tabs: [
 *         {label: 'Content'|t, panel: contentHtml},
 *         {label: 'Settings'|t, panel: settingsHtml, disabled: true},
 *     ]}) }}
 *
 * The web component pairs tabs to panels by document order, so this class owns
 * the emission of both halves — a tab and its panel are added together and
 * rendered as siblings, which is what keeps the pairing honest. Panels are
 * wrapped in a `<div slot="panel">`; pass an `Htmlable` for trusted markup
 * (plain strings are encoded).
 *
 * Renders directly (no Blade view) — the chrome lives in the web component.
 */
class Tabs extends ViewComponent
{
    use HasSize;

    /** @var list<Tab> */
    protected array $items = [];

    protected ?int $selectedIndex = null;

    protected TabsLayout|string|null $layout = null;

    protected TabsPlacement|string|null $placement = null;

    protected bool $collapsible = false;

    protected function tagName(): string
    {
        return 'craft-tabs';
    }

    /**
     * Adds a tab and the panel it reveals. Accepts a prepared {@see Tab} (whose
     * own panel is kept unless one is passed here) or a plain label.
     */
    public function tab(
        Tab|string $tab,
        string|Htmlable|Stringable|ViewComponent|null $panel = null,
    ): static {
        if (! $tab instanceof Tab) {
            $tab = Tab::make()->label($tab);
        }

        if ($panel !== null) {
            $tab->panel($panel);
        }

        $this->items[] = $tab;

        return $this;
    }

    /**
     * Replaces the tabs. Each item may be a {@see Tab} or a config array
     * (`['label' => …, 'panel' => …]`), so the whole strip can come from a
     * Twig hash.
     *
     * @param  iterable<array-key, Tab|array<string, mixed>>  $tabs
     */
    public function tabs(iterable $tabs): static
    {
        $this->items = [];

        foreach ($tabs as $tab) {
            $this->items[] = match (true) {
                $tab instanceof Tab => $tab,
                is_array($tab) => Tab::make()->configure($tab),
                default => throw new InvalidArgumentException(sprintf(
                    'Tabs::tabs() expects %s instances or config arrays, got %s.',
                    Tab::class,
                    get_debug_type($tab),
                )),
            };
        }

        return $this;
    }

    /** The index of the initially selected tab; the web component defaults to 0. */
    public function selectedIndex(?int $selectedIndex): static
    {
        $this->selectedIndex = $selectedIndex;

        return $this;
    }

    /**
     * Where the strip sits relative to its panels; the web component defaults
     * to `block-start`. Strings (e.g. from Twig `ui()` config) are validated
     * against {@see TabsPlacement}.
     */
    public function placement(TabsPlacement|string|null $placement): static
    {
        $this->placement = $placement;

        return $this;
    }

    public function getPlacement(): ?string
    {
        if ($this->placement === null) {
            return null;
        }

        return $this->placement instanceof TabsPlacement
            ? $this->placement->value
            : TabsPlacement::from($this->placement)->value;
    }

    /**
     * Lets the selected tab be deselected, closing the panel region entirely —
     * what an icon toolbar wants, where "nothing open" is a normal state.
     */
    public function collapsible(bool $collapsible = true): static
    {
        $this->collapsible = $collapsible;

        return $this;
    }

    /**
     * Which axis the strip runs along; the web component defaults to
     * `horizontal`. Strings (e.g. from Twig `ui()` config) are validated
     * against {@see TabsLayout}.
     *
     * @deprecated Use {@see placement()}, which says which edge the strip sits
     *   on rather than only which way it runs.
     */
    public function layout(TabsLayout|string|null $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    public function getLayout(): ?string
    {
        if ($this->layout === null) {
            return null;
        }

        return $this->layout instanceof TabsLayout ? $this->layout->value : TabsLayout::from($this->layout)->value;
    }

    /**
     * Emits each tab immediately followed by its panel, which is the order the
     * web component pairs them in. Any content assigned to the component's own
     * slots follows.
     *
     * A tab naming an external panel gets none emitted here — that panel is
     * rendered elsewhere on the page, and a placeholder would just be dead DOM
     * inside the strip. Tabs without one always get a panel element, empty or
     * not, because the pairing is positional.
     */
    #[\Override]
    protected function renderSlots(): string
    {
        $html = '';

        foreach ($this->items as $tab) {
            $html .= $tab->toHtml();

            if ($tab->getControls() === null) {
                $html .= Html::tag('div', $this->renderContent($tab->getPanel()), ['slot' => 'panel']);
            }
        }

        return $html.parent::renderSlots();
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'selected-index' => $this->selectedIndex,
            'size' => $this->getSize(),
            'placement' => $this->getPlacement(),
            'collapsible' => $this->collapsible,
            'layout' => $this->getLayout(),
        ];
    }
}
