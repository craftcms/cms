<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Data;

use Illuminate\Contracts\Support\Arrayable;

/**
 * The parts an element card is rendered from, whether the card is rendered as
 * a single HTML string or by a card component's slots.
 *
 * @implements Arrayable<string, mixed>
 *
 * @since 6.0.0
 */
final class ElementCardParts implements Arrayable
{
    /**
     * @param  array<string, mixed>  $attributes  The card’s HTML attributes
     * @param  string  $headerHtml  The card’s label (icon, title, modified status)
     * @param  string  $actionsHtml  The card’s actions (edit button, action menu, drag handle)
     * @param  string  $contentHtml  The card’s `.card-content` HTML
     * @param  string  $footerHtml  The card’s footer (e.g. the hidden input in the `field` context)
     * @param  string  $thumbHtml  The card’s thumbnail
     * @param  'start'|'end'  $thumbAlignment  Which side of the content the thumbnail goes on
     */
    public function __construct(
        public array $attributes = [],
        public string $headerHtml = '',
        public string $actionsHtml = '',
        public string $contentHtml = '',
        public string $footerHtml = '',
        public string $thumbHtml = '',
        public string $thumbAlignment = 'end',
    ) {}

    /**
     * @return array{
     *     attributes: array<string, mixed>,
     *     headerHtml: string,
     *     actionsHtml: string,
     *     contentHtml: string,
     *     footerHtml: string,
     *     thumbHtml: string,
     *     thumbAlignment: string,
     * }
     */
    #[\Override]
    public function toArray(): array
    {
        return [
            'attributes' => $this->attributes,
            'headerHtml' => $this->headerHtml,
            'actionsHtml' => $this->actionsHtml,
            'contentHtml' => $this->contentHtml,
            'footerHtml' => $this->footerHtml,
            'thumbHtml' => $this->thumbHtml,
            'thumbAlignment' => $this->thumbAlignment,
        ];
    }
}
