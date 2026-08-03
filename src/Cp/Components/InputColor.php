<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Support\Json;

/**
 * PHP counterpart to the `<craft-input-color>` web component — a hex text input
 * paired with a native color-picker swatch and an optional preset datalist, the
 * modern replacement for the legacy `Craft.ColorInput` JS.
 *
 * It's an {@see Input} whose tag is fixed to craft-input-color. Like
 * {@see InputPassword}, the element extends LionInput directly (not craft-input),
 * so only the Lion-pushed control props live on the host; the value is stored as
 * bare hex (the component renders its own leading `#`). Presets are passed as a
 * JSON array attribute the component parses into its picker datalist.
 *
 *     InputColor::make()
 *         ->name('color')
 *         ->value('7ab55c')
 *         ->presets(['#ffffff', '#000000']);
 */
class InputColor extends Input
{
    /** @var array<int, string> */
    protected array $presets = [];

    #[\Override]
    protected function tagName(): string
    {
        return 'craft-input-color';
    }

    /**
     * Preset colors offered in the picker's datalist. Each may be `#`-prefixed
     * or bare hex; the component drops invalid entries.
     *
     * @param  array<int, string>  $presets
     */
    public function presets(array $presets): static
    {
        $this->presets = $presets;

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        $attributes = parent::hostAttributes();

        // craft-input-color extends LionInput directly (see InputPassword for the
        // same rationale): keep only the Lion-pushed control props on the host,
        // drop `type` and the craft-input-only styling attributes. The slotted
        // native input still carries the functional ones via Input::inputHtml().
        foreach (['type', 'maxlength', 'size', 'small', 'width', 'center', 'monospace', 'hidden-input'] as $key) {
            unset($attributes[$key]);
        }

        $presets = array_values(array_filter($this->presets));
        $attributes['presets'] = $presets !== [] ? Json::encode($presets) : null;

        return $attributes;
    }
}
