<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Support\Html;

/**
 * Radio group container — the PHP counterpart to the `<craft-radio-group>`
 * web component (and to the legacy `_includes/forms/radioGroup` template).
 * Renders {@see Radio} options, each in its own wrapper; a single value posts
 * under the shared name, so there is no always-post hidden input. The web
 * component adopts that shared name from the slotted radio inputs.
 *
 *     RadioGroup::make()
 *         ->id('mode')
 *         ->options([
 *             Radio::make()->label(t('Auto'))->name('mode')->value('auto'),
 *             Radio::make()->label(t('Manual'))->name('mode')->value('manual'),
 *         ]);
 */
class RadioGroup extends ChoiceGroup
{
    protected bool $toggle = false;

    protected ?string $targetPrefix = null;

    protected function tagName(): string
    {
        return 'craft-radio-group';
    }

    /** Marks the group as a field toggle (reveals `{targetPrefix}{value}` containers). */
    public function toggle(bool $toggle = true): static
    {
        $this->toggle = $toggle;

        return $this;
    }

    public function targetPrefix(?string $targetPrefix): static
    {
        $this->targetPrefix = $targetPrefix;

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'id' => $this->getId(),
            'class' => array_filter([
                'radio-group',
                $this->toggle ? 'fieldtoggle' : null,
            ]),
            // Lays the options out as a row; see `craft-radio-group`'s styles.
            'thumbnails' => $this->hasThumbnails(),
            'data' => [
                'target-prefix' => $this->toggle ? ($this->targetPrefix ?? '#') : null,
            ],
        ];
    }

    /**
     * Renders a Radio's thumbnail above it, in its own `label` so clicking the
     * illustration selects the option — the behaviour Craft 5 got from wrapping
     * the image and the input in one `label`.
     */
    #[\Override]
    protected function optionLeadingHtml(ViewComponent $option): string
    {
        $thumbnail = $option instanceof Radio ? $option->getThumbnail() : null;

        if ($thumbnail === null) {
            return '';
        }

        $aspectRatio = $thumbnail['aspectRatio'] ?? null;

        return Html::tag('label', Html::tag('img', '', [
            'src' => $thumbnail['src'],
            'width' => $thumbnail['width'] ?? null,
            'height' => $thumbnail['height'] ?? null,
            'style' => $aspectRatio !== null ? ['aspect-ratio' => $aspectRatio] : null,
            'alt' => '',
        ]), [
            'class' => 'radio-thumbnail',
            'for' => $option->getId(),
        ]);
    }

    private function hasThumbnails(): bool
    {
        foreach ($this->options as $option) {
            if ($option instanceof Radio && $option->getThumbnail() !== null) {
                return true;
            }
        }

        return false;
    }
}
