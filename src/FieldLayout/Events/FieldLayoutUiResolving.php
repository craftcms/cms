<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;

/**
 * @event FieldLayoutUiResolving The event that is triggered when resolving a field layout into a UI.
 *
 * ```php
 * use CraftCms\Cms\FieldLayout\Events\FieldLayoutUiResolving;
 * use CraftCms\Cms\Ui\Nodes\MarkdownContent;
 * use Illuminate\Support\Facades\Event;
 *
 * Event::listen(function (FieldLayoutUiResolving $event) {
 *     $event->ui->add(MarkdownContent::make('notice', 'Remember to save your changes.'));
 * });
 * ```
 *
 * @see FieldLayoutCompiler::compile()
 * @since 6.0.0
 */
class FieldLayoutUiResolving
{
    public function __construct(
        public FieldLayout $fieldLayout,
        public Ui $ui,
        public UiContext $context,
        public ?ElementInterface $element = null,
    ) {}
}
