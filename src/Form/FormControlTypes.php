<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form;

use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\Address;
use CraftCms\Cms\Form\Controls\AssetSelect;
use CraftCms\Cms\Form\Controls\Checkbox;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Color;
use CraftCms\Cms\Form\Controls\Combobox;
use CraftCms\Cms\Form\Controls\ConditionBuilder;
use CraftCms\Cms\Form\Controls\ContentBlock;
use CraftCms\Cms\Form\Controls\Date;
use CraftCms\Cms\Form\Controls\DateTime;
use CraftCms\Cms\Form\Controls\ElementSelect;
use CraftCms\Cms\Form\Controls\FieldLayoutDesigner;
use CraftCms\Cms\Form\Controls\FieldSelect;
use CraftCms\Cms\Form\Controls\FilesystemSelect;
use CraftCms\Cms\Form\Controls\GroupedEntryTypeManager;
use CraftCms\Cms\Form\Controls\Handle;
use CraftCms\Cms\Form\Controls\Hidden;
use CraftCms\Cms\Form\Controls\IconPicker;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Link;
use CraftCms\Cms\Form\Controls\Markdown;
use CraftCms\Cms\Form\Controls\Matrix;
use CraftCms\Cms\Form\Controls\Missing;
use CraftCms\Cms\Form\Controls\Money;
use CraftCms\Cms\Form\Controls\Number;
use CraftCms\Cms\Form\Controls\PermissionTree;
use CraftCms\Cms\Form\Controls\Range;
use CraftCms\Cms\Form\Controls\Table;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Controls\Textarea;
use CraftCms\Cms\Form\Controls\Time;
use Illuminate\Container\Attributes\Singleton;

/**
 * Registers Control type classes available to Control Panel Forms.
 *
 * @extends TypeRegistry<Control>
 */
#[Singleton]
class FormControlTypes extends TypeRegistry
{
    protected const string CONTRACT = Control::class;

    protected const array DEFAULT_TYPES = [
        Address::class,
        AssetSelect::class,
        Checkbox::class,
        Choice::class,
        ConditionBuilder::class,
        Color::class,
        Combobox::class,
        ContentBlock::class,
        Date::class,
        DateTime::class,
        ElementSelect::class,
        FieldLayoutDesigner::class,
        FieldSelect::class,
        FilesystemSelect::class,
        GroupedEntryTypeManager::class,
        Handle::class,
        Hidden::class,
        IconPicker::class,
        Lightswitch::class,
        Link::class,
        Markdown::class,
        Matrix::class,
        Missing::class,
        Money::class,
        Number::class,
        PermissionTree::class,
        Range::class,
        Table::class,
        Text::class,
        Textarea::class,
        Time::class,
    ];
}
