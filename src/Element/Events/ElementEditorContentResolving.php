<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * @event ElementEditorContentResolving The event that is triggered when rendering an element editor’s content.
 */
class ElementEditorContentResolving
{
    public function __construct(
        public ElementInterface $element,
        public string $html = '',
        public bool $static = false,
    ) {}
}
