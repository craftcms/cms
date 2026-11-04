<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Data;

/**
 * The parts an element card is rendered from, whether the card is rendered as
 * a single HTML string or by a card component’s slots.
 *
 * @since 6.0.0
 */
class ElementCardParts
{
    /**
     * @param  array<string, mixed>  $attributes  The card’s HTML attributes
     * @param  string  $labelHtml  The card’s label (icon, title, modified status)
     * @param  string  $actionsHtml  The card’s actions (edit button, action menu, drag handle)
     * @param  string  $contentHtml  The card’s `.card-content` HTML
     * @param  string  $footerHtml  The card’s footer (e.g. the hidden input in the `field` context)
     * @param  string  $thumbHtml  The card’s thumbnail
     * @param  'start'|'end'  $thumbAlignment  Which side of the content the thumbnail goes on
     */
    public function __construct(
        public array $attributes = [],
        public string $labelHtml = '',
        public string $actionsHtml = '',
        public string $contentHtml = '',
        public string $footerHtml = '',
        public string $thumbHtml = '',
        public string $thumbAlignment = 'end',
    ) {}
}
