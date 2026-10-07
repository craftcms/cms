<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui;

use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Controls\Address;
use CraftCms\Cms\Ui\Controls\AssetSelect;
use CraftCms\Cms\Ui\Controls\Checkbox;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Controls\Color;
use CraftCms\Cms\Ui\Controls\ColorSelect;
use CraftCms\Cms\Ui\Controls\Combobox;
use CraftCms\Cms\Ui\Controls\ConditionBuilder;
use CraftCms\Cms\Ui\Controls\ContentBlock;
use CraftCms\Cms\Ui\Controls\Date;
use CraftCms\Cms\Ui\Controls\DateTime;
use CraftCms\Cms\Ui\Controls\ElementSelect;
use CraftCms\Cms\Ui\Controls\EntryTypeSelect;
use CraftCms\Cms\Ui\Controls\FieldLayoutDesigner;
use CraftCms\Cms\Ui\Controls\FieldSelect;
use CraftCms\Cms\Ui\Controls\GroupedEntryTypeManager;
use CraftCms\Cms\Ui\Controls\Handle;
use CraftCms\Cms\Ui\Controls\Hidden;
use CraftCms\Cms\Ui\Controls\IconPicker;
use CraftCms\Cms\Ui\Controls\Lightswitch;
use CraftCms\Cms\Ui\Controls\Link;
use CraftCms\Cms\Ui\Controls\Markdown;
use CraftCms\Cms\Ui\Controls\Missing;
use CraftCms\Cms\Ui\Controls\Money;
use CraftCms\Cms\Ui\Controls\NestedElementBlocks;
use CraftCms\Cms\Ui\Controls\NestedElements;
use CraftCms\Cms\Ui\Controls\Number;
use CraftCms\Cms\Ui\Controls\PermissionTree;
use CraftCms\Cms\Ui\Controls\Range;
use CraftCms\Cms\Ui\Controls\Slug;
use CraftCms\Cms\Ui\Controls\Table;
use CraftCms\Cms\Ui\Controls\TableColumns;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Controls\Textarea;
use CraftCms\Cms\Ui\Controls\Time;
use CraftCms\Cms\Ui\Controls\UserGroupSelect;
use Illuminate\Container\Attributes\Singleton;

/**
 * Registers Control type classes available to Control Panel Forms.
 *
 * @extends TypeRegistry<Control>
 *
 * @since 6.0.0
 */
#[Singleton]
class UiControlTypes extends TypeRegistry
{
    protected const string CONTRACT = Control::class;

    protected const array DEFAULT_TYPES = [
        Address::class,
        AssetSelect::class,
        Checkbox::class,
        Choice::class,
        ConditionBuilder::class,
        Color::class,
        ColorSelect::class,
        Combobox::class,
        ContentBlock::class,
        Date::class,
        DateTime::class,
        ElementSelect::class,
        EntryTypeSelect::class,
        FieldLayoutDesigner::class,
        FieldSelect::class,
        GroupedEntryTypeManager::class,
        Handle::class,
        Hidden::class,
        IconPicker::class,
        Lightswitch::class,
        Link::class,
        Markdown::class,
        Missing::class,
        Money::class,
        NestedElementBlocks::class,
        NestedElements::class,
        Number::class,
        PermissionTree::class,
        Range::class,
        Slug::class,
        Table::class,
        TableColumns::class,
        Text::class,
        Textarea::class,
        Time::class,
        UserGroupSelect::class,
    ];
}
