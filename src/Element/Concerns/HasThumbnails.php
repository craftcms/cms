<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use CraftCms\Cms\Support\Html;

/**
 * Provides thumbnail functionality for elements.
 *
 * This trait handles methods related to rendering element thumbnails
 * in the Control Panel, including URL generation, SVG fallbacks,
 * and styling options.
 *
 * @internal
 */
trait HasThumbnails
{
    /**
     * Returns the element's thumbnail HTML.
     *
     * @param  int  $size  The maximum width and height the thumbnail should have.
     */
    public function getThumbHtml(int $size): ?string
    {
        $thumbField = $this->getFieldLayout()?->getThumbField();

        if ($thumbField && $thumbHtml = $thumbField->thumbHtml($this, $size)) {
            return $thumbHtml;
        }

        if ($thumbUrl = $this->thumbUrl($size)) {
            return $this->renderImageThumb($size, $thumbUrl);
        }

        if ($thumbSvg = $this->thumbSvg()) {
            return $this->renderSvgThumb($thumbSvg);
        }

        return null;
    }

    private function renderImageThumb(int $size, string $thumbUrl): string
    {
        return Html::tag('div', '', [
            'class' => [
                'thumb',
                $this->hasCheckeredThumb() ? 'checkered' : null,
                $this->hasRoundedThumb() ? 'rounded' : null,
            ],
            'data' => [
                'sizes' => "calc({$size}rem/16)",
                'srcset' => "{$thumbUrl} {$size}w, {$this->thumbUrl($size * 2)} ".($size * 2).'w',
                'alt' => $this->thumbAlt(),
                'animated' => $this->couldHaveAnimatedThumb() ?: null,
            ],
        ]);
    }

    private function renderSvgThumb(string $thumbSvg): string
    {
        $thumbSvg = Html::svg($thumbSvg, sanitize: false, namespace: true);

        if ($alt = $this->thumbAlt()) {
            $thumbSvg = Html::prependToTag($thumbSvg, Html::tag('title', Html::encode($alt)), ifExists: 'replace');
        }

        $thumbSvg = Html::modifyTagAttributes($thumbSvg, ['role' => 'img']);

        return Html::tag('div', $thumbSvg, [
            'class' => [
                'thumb',
                $this->hasRoundedThumb() ? 'rounded' : null,
            ],
        ]);
    }

    /**
     * Returns the URL to the element's thumbnail, if it has one.
     *
     * @param  int  $size  The maximum width and height the thumbnail should have.
     *
     * @since 5.0.0
     */
    protected function thumbUrl(int $size): ?string
    {
        return null;
    }

    /**
     * Returns the element's thumbnail SVG contents, which should be used as a fallback when [[getThumbUrl()]]
     * returns `null`.
     *
     * @since 4.5.0
     */
    protected function thumbSvg(): ?string
    {
        return null;
    }

    /**
     * Returns alt text for the element's thumbnail.
     *
     * @since 5.0.0
     */
    protected function thumbAlt(): ?string
    {
        return null;
    }

    /**
     * Returns whether the element's thumbnail should have a checkered background.
     *
     * @since 5.0.0
     */
    protected function hasCheckeredThumb(): bool
    {
        return false;
    }

    /**
     * Returns whether the element's thumbnail should be rounded.
     *
     * @since 5.0.0
     */
    protected function hasRoundedThumb(): bool
    {
        return false;
    }

    /**
     * Returns whether the element's thumbnail is potentially animated.
     *
     * @since 5.7.0
     */
    protected function couldHaveAnimatedThumb(): bool
    {
        return false;
    }
}
