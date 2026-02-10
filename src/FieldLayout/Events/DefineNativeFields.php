<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Events;

use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;

/**
 * @event DefineNativeFields The event that is triggered when defining the native (not custom) fields for the layout.
 *
 * Note that fields set on [[DefineNativeFields::$fields]] will be grouped by field group, indexed by the group names.
 *
 * ```php
 * use CraftCms\Cms\FieldLayout\FieldLayout;
 * use CraftCms\Cms\FieldLayout\FieldLayout\Events\DefineNativeFields;
 * use CraftCms\Cms\Field\PlainText;
 * use Illuminate\Support\Facades\Event;
 *
 * Event::listen(function (DefineNativeFields $event) {
 *     $layout = $event->fieldLayout;
 *
 *     if ($layout->type === MyElementType::class) {
 *         $event->fields[] = MyNativeField::class;
 *     }
 * });
 * ```
 *
 * @see FieldLayout::getAvailableNativeFields()
 */
final class DefineNativeFields
{
    public function __construct(
        public FieldLayout $fieldLayout,

        /**
         * @var array<BaseField|class-string<BaseField>|array{class:class-string<BaseField>}> The fields that should be available to the field layout designer.
         */
        public array $fields,
    ) {}
}
