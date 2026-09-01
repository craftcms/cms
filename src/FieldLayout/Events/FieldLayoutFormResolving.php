<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;

/**
 * @event FieldLayoutFormResolving The event that is triggered when resolving a field layout into a form.
 *
 * ```php
 * use CraftCms\Cms\FieldLayout\Events\FieldLayoutFormResolving;
 * use CraftCms\Cms\Form\Nodes\MarkdownContent;
 * use Illuminate\Support\Facades\Event;
 *
 * Event::listen(function (FieldLayoutFormResolving $event) {
 *     $event->form->add(MarkdownContent::make('notice', 'Remember to save your changes.'));
 * });
 * ```
 *
 * @see FieldLayoutCompiler::compile()
 */
class FieldLayoutFormResolving
{
    public function __construct(
        public FieldLayout $fieldLayout,
        public Form $form,
        public FormContext $context,
        public ?ElementInterface $element = null,
    ) {}
}
