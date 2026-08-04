<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form;

use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Color;
use CraftCms\Cms\Form\Controls\Date;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Money;
use CraftCms\Cms\Form\Controls\Number;
use CraftCms\Cms\Form\Controls\Range;
use CraftCms\Cms\Form\Controls\Select;
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
        Text::class,
        Textarea::class,
        Select::class,
        Lightswitch::class,
        Choice::class,
        Number::class,
        Range::class,
        Date::class,
        Time::class,
        Color::class,
        Money::class,
    ];
}
