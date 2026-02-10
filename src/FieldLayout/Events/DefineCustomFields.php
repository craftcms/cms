<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Events;

use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;

/**
 * @event DefineCustomFields The event that is triggered when defining the custom fields for the layout.
 *
 * Note that fields set on [[DefineCustomFields::$fields]] will be grouped by field group, indexed by the group names.
 *
 * ```php
 * use CraftCms\Cms\FieldLayout\FieldLayout;
 * use CraftCms\Cms\FieldLayout\FieldLayout\Events\DefineCustomFields;
 * use CraftCms\Cms\Field\PlainText;
 * use Illuminate\Support\Facades\Event;
 *
 * Event::listen(function (DefineCustomFields $event) {
 *     $layout = $event->fieldLayout;
 *
 *     if ($layout->type === MyElementType::class) {
 *         // Only allow Plain Text fields
 *         foreach ($event->fields as $groupName => &$fields) {
 *             $fields = array_filter($fields, fn($field) => $field instanceof PlainText);
 *         }
 *     }
 * });
 * ```
 *
 * @see FieldLayout::getAvailableCustomFields()
 */
final class DefineCustomFields
{
    public function __construct(
        public FieldLayout $fieldLayout,

        /**
         * @var BaseField[][] The custom fields that should be available to the field layout designer.
         */
        public array $fields,
    ) {}
}
