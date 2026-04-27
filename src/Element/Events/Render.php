<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * @event Render event is triggered before an element is rendered.
 *
 * If `output` is set, it will be used as the rendered output instead of looking for templates.
 */
class Render
{
    /**
     * @param  ElementInterface  $element  The element being rendered
     * @param  array{template:string,priority:int}[]  $templates  The template paths to check when rendering the element's partial template
     * @param  array  $variables  Additional variables to be passed to the template
     * @param  string|null  $output  The output of the event (if set, short-circuits template rendering)
     */
    public function __construct(
        public ElementInterface $element,
        public array $templates,
        public array $variables,
        public ?string $output = null,
    ) {}
}
