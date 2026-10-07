<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;

/**
 * @event FieldLayoutFormResolving The event that is triggered when resolving a field layout into a form.
 *
 * ```php
 * use CraftCms\Cms\FieldLayout\Events\FieldLayoutFormResolving;
 * use CraftCms\Cms\Ui\Nodes\MarkdownContent;
 * use Illuminate\Support\Facades\Event;
 *
 * Event::listen(function (FieldLayoutFormResolving $event) {
 *     $event->form->add(MarkdownContent::make('notice', 'Remember to save your changes.'));
 * });
 * ```
 *
 * @see FieldLayoutCompiler::compile()
 * @since 6.0.0
 */
class FieldLayoutFormResolving
{
    public function __construct(
        public FieldLayout $fieldLayout,
        public Ui $form,
        public UiContext $context,
        public ?ElementInterface $element = null,
    ) {}
}
