<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Mcp\Events\ElementSerializing;
use CraftCms\Cms\Support\Arr;

/**
 * @since 6.0.0
 */
class ElementSerializer
{
    /**
     * @param  array<string, mixed>|null  $data
     * @return array<string, mixed>
     */
    public function serialize(
        ElementInterface $element,
        ?array $data = null,
        bool $public = false,
        bool $filterNulls = true,
    ): array {
        $event = new ElementSerializing(
            element: $element,
            data: $data ?? ['type' => $element::class, ...$element->toArray()],
            public: $public,
        );

        event($event);

        return $filterNulls ? Arr::whereNotNull($event->data) : $event->data;
    }
}
