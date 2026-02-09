<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\FieldLayout\Data\FieldLayoutForm;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;

/**
 * @event CreateFieldLayoutForm The event that is triggered when creating a new field layout form.
 *
 * ```php
 * use CraftCms\Cms\Entry\Elements\Entry;
 * use CraftCms\Cms\FieldLayout\Events\CreateFieldLayoutForm;
 * use CraftCms\Cms\FieldLayout\LayoutElements\HorizontalRule;
 * use CraftCms\Cms\FieldLayout\LayoutElements\StandardTextField;
 * use CraftCms\Cms\FieldLayout\LayoutElements\Template;
 * use CraftCms\Cms\FieldLayout\FieldLayout;
 * use CraftCms\Cms\FieldLayout\FieldLayoutTab;
 * use Illuminate\Support\Facades\Event;
 *
 * Event::listen(function (CreateFieldLayoutForm $event) {
 *     if (! $event->element instanceof Entry) {
 *         return;
 *     }
 *
 *     $event->tabs[] = new FieldLayoutTab([
 *         'name' => 'My Tab',
 *         'elements' => [
 *             new StandardTextField([
 *                 'attribute' => 'myTextField',
 *                 'label' => 'My Text Field',
 *             ]),
 *             new HorizontalRule(),
 *             new Template([
 *                 'template' => '_layout-elements/info'
 *             ]),
 *         ],
 *     ]);
 * });
 * ```
 *
 * @see FieldLayout::createForm()
 */
final class CreateFieldLayoutForm
{
    public function __construct(
        public FieldLayout $fieldLayout,

        /**
         * @var \CraftCms\Cms\FieldLayout\Data\FieldLayoutForm The field layout form being created
         */
        public FieldLayoutForm $form,

        /**
         * @var ElementInterface|null The element the form is being rendered for
         */
        public ?ElementInterface $element = null,

        /**
         * @var bool Whether the form should be static (non-interactive)
         */
        public bool $static = false,

        /**
         * @var FieldLayoutTab[] The field layout tabs that will be displayed in the form.
         */
        public array $tabs = [],
    ) {}
}
