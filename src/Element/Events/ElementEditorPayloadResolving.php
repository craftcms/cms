<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * @event ElementEditorPayloadResolving The event that is triggered after preparing an element editor's payload.
 *
 * @since 6.0.0
 */
class ElementEditorPayloadResolving
{
    /** @param array<string, mixed> $data */
    public function __construct(
        public ElementInterface $element,
        public array $data,
        public string $containerId,
    ) {}
}
