<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Form\Enums\AllOptionMode;

/**
 * PHP counterpart to the `<craft-checkbox-indeterminate>` web component: an
 * “All” checkbox that owns the {@see Checkbox} options it governs.
 *
 * The options nest inside it rather than sitting beside it — that's how the
 * component finds the boxes it toggles — and it reflects their state back as
 * checked, unchecked, or indeterminate when only some are on.
 *
 *     CheckboxIndeterminate::make()
 *         ->label(t('All'))
 *         ->name('sources[]')
 *         ->value('*')
 *         ->children([
 *             Checkbox::make()->label(t('Uploads'))->name('sources[]')->value('volume:1'),
 *         ]);
 *
 * In {@see AllOptionMode::SingleValue} mode the
 * options render disabled while “All” is checked, so only its own value posts.
 * Disabled is what suppresses them — they stay checked, so the group still
 * reads as fully selected.
 */
class CheckboxIndeterminate extends Checkbox
{
    #[\Override]
    protected function tagName(): string
    {
        return 'craft-checkbox-indeterminate';
    }

    /** The group renders its own always-post input; a second would post an empty value. */
    #[\Override]
    protected function rendersAlwaysPostInput(): bool
    {
        return false;
    }

    /**
     * The checkboxes this one governs, rendered after its own input and label.
     *
     * @param  iterable<array-key, mixed>  $children
     */
    public function children(iterable $children): static
    {
        $this->slots[static::DEFAULT_SLOT] = $children;

        return $this;
    }
}
