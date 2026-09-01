<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use Illuminate\Container\Attributes\Singleton;

/**
 * Registers field type classes available to Craft.
 *
 * ```php
 * public function boot(FieldTypes $fieldTypes): void
 * {
 *     $fieldTypes->register(MyField::class);
 * }
 * ```
 *
 * @extends TypeRegistry<FieldInterface>
 */
#[Singleton]
class FieldTypes extends TypeRegistry
{
    protected const string CONTRACT = FieldInterface::class;

    protected const array DEFAULT_TYPES = [
        Addresses::class,
        Assets::class,
        ButtonGroup::class,
        Checkboxes::class,
        Color::class,
        ContentBlock::class,
        Country::class,
        Date::class,
        Dropdown::class,
        Email::class,
        Entries::class,
        Icon::class,
        Json::class,
        Lightswitch::class,
        Link::class,
        Markdown::class,
        Matrix::class,
        Money::class,
        MultiSelect::class,
        Number::class,
        PlainText::class,
        RadioButtons::class,
        Range::class,
        Table::class,
        Time::class,
        Users::class,
    ];
}
