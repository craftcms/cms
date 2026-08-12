<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Concerns\HasDisabled;
use Illuminate\Contracts\Support\Htmlable;
use Stringable;

/**
 * PHP counterpart to the `<craft-tab>` web component — one trigger within a
 * {@see Tabs} strip, along with the panel it reveals:
 *
 *     Tab::make()
 *         ->label(t('Settings'))
 *         ->panel($settingsHtml);
 *
 * A tab carries its panel even though the two render as separate sibling
 * elements, because the web component pairs them positionally (the nth
 * `slot="tab"` child owns the nth `slot="panel"` child). Keeping the pair in
 * one object is what stops a caller from silently misaligning them; {@see Tabs}
 * pulls the panel back out at render time via {@see self::getPanel()}.
 *
 * Selection is not settable here: `<craft-tabs>` owns the `selected` attribute
 * and overwrites whatever the server sent. Use {@see Tabs::selectedIndex()}.
 */
class Tab extends ViewComponent
{
    use HasDisabled;

    protected string|Htmlable|Stringable|ViewComponent|null $panel = null;

    protected ?string $controls = null;

    protected function tagName(): string
    {
        return 'craft-tab';
    }

    /** The tab's label (default slot). */
    public function label(string|Htmlable|Stringable|ViewComponent|null $label): static
    {
        $this->slots[static::DEFAULT_SLOT] = $label;

        return $this;
    }

    /**
     * The content this tab reveals. Rendered by {@see Tabs} as a sibling of
     * the tab, not as a child of it.
     */
    public function panel(string|Htmlable|Stringable|ViewComponent|null $panel): static
    {
        $this->panel = $panel;

        return $this;
    }

    public function getPanel(): string|Htmlable|Stringable|ViewComponent|null
    {
        return $this->panel;
    }

    /**
     * The `id` of a panel rendered elsewhere in the document, for strips whose
     * panels can't sit alongside their tabs. Setting it on every tab in a
     * {@see Tabs} puts the web component into external-panel mode; the tabs
     * then carry no {@see self::panel()} content of their own.
     */
    public function controls(?string $controls): static
    {
        $this->controls = $controls;

        return $this;
    }

    public function getControls(): ?string
    {
        return $this->controls;
    }

    /**
     * `slot` is fixed rather than merged from {@see ViewComponent::slot()}: a
     * tab is only ever a `tab`-slotted child of `<craft-tabs>`, and landing in
     * any other slot would drop it out of the tablist entirely.
     */
    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'slot' => 'tab',
            'disabled' => $this->isDisabled(),
            'controls' => $this->controls,
        ];
    }
}
