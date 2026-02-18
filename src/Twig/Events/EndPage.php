<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Events;

/**
 * @event EndPage The event that is triggered when page rendering ends.
 */
final class EndPage
{
    public function __construct(
        public ?string $headHtml = null,
        public ?string $bodyBeginHtml = null,
        public ?string $bodyEndHtml = null,
    ) {}
}
