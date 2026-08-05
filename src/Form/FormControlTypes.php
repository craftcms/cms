<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form;

use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\Address;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Color;
use CraftCms\Cms\Form\Controls\Date;
use CraftCms\Cms\Form\Controls\ElementSelect;
use CraftCms\Cms\Form\Controls\IconPicker;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Link;
use CraftCms\Cms\Form\Controls\Markdown;
use CraftCms\Cms\Form\Controls\Money;
use CraftCms\Cms\Form\Controls\Number;
use CraftCms\Cms\Form\Controls\Range;
use CraftCms\Cms\Form\Controls\Select;
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
        Choice::class,
        Color::class,
        Date::class,
        ElementSelect::class,
        IconPicker::class,
        Lightswitch::class,
        Link::class,
        Markdown::class,
        Money::class,
        Number::class,
        Range::class,
        Select::class,
        Table::class,
        Text::class,
        Textarea::class,
        Time::class,
    ];
}
