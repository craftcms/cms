<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Support\Html;

use function CraftCms\Cms\t;

/**
 * PHP counterpart to the `<craft-radio>` web component. Shares the light-DOM
 * SSR surface with {@see Checkbox} — a native input and label the web
 * component adopts — with radio posting semantics: no always-post hidden
 * input (the group posts a single value), and custom-option mode labeled
 * "Other:" with the text input in its own wrapper.
 *
 * @since 6.0.0
 */
class Radio extends Checkbox
{
    /** @var array{src: string, width?: int|null, height?: int|null, aspectRatio?: string|null}|null */
    protected ?array $thumbnail = null;

    #[\Override]
    protected function tagName(): string
    {
        return 'craft-radio';
    }

    /**
     * An illustration of what the option looks like. A {@see RadioGroup} with
     * thumbnailed options shows each one as a tile, with the illustration in
     * a button-like box and the label below it.
     *
     * `width` and `height` set the intrinsic size, reserving space before the
     * image loads. `aspectRatio` maps to the CSS property of the same name, and
     * wins over those dimensions for layout when both are given.
     *
     * @param  array{src: string, width?: int|null, height?: int|null, aspectRatio?: string|null}|null  $thumbnail
     */
    public function thumbnail(?array $thumbnail): static
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    /** @return array{src: string, width?: int|null, height?: int|null, aspectRatio?: string|null}|null */
    public function getThumbnail(): ?array
    {
        return $this->thumbnail;
    }

    /**
     * Renders the thumbnail into the radio's `thumbnail` slot, which a
     * `craft-radio-group` with `thumbnails` draws as the tile's illustration.
     * Its `alt` is empty, since the label already names the option.
     */
    #[\Override]
    protected function renderSlots(): string
    {
        if ($this->thumbnail === null) {
            return parent::renderSlots();
        }

        $aspectRatio = $this->thumbnail['aspectRatio'] ?? null;

        return Html::tag('img', '', [
            'slot' => 'thumbnail',
            'src' => $this->thumbnail['src'],
            'width' => $this->thumbnail['width'] ?? null,
            'height' => $this->thumbnail['height'] ?? null,
            'style' => $aspectRatio !== null ? ['aspect-ratio' => $aspectRatio] : null,
            'alt' => '',
        ]).parent::renderSlots();
    }

    /** Radios have no indeterminate state, so drop the Checkbox host attribute. */
    #[\Override]
    protected function hostAttributes(): array
    {
        return [];
    }

    #[\Override]
    /** @return array<string, mixed> */
    protected function inputDefaults(): array
    {
        return [
            'type' => 'radio',
            'class' => ['radio'],
        ];
    }

    #[\Override]
    protected function rendersAlwaysPostInput(): bool
    {
        return false;
    }

    #[\Override]
    protected function customLabelText(): string
    {
        return t('Other:');
    }

    #[\Override]
    protected function customInputHtml(): string
    {
        $html = parent::customInputHtml();

        return $html === '' ? '' : Html::tag('div', $html, ['class' => 'custom-option-wrapper']);
    }
}
