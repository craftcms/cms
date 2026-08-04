<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form;

use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Select;
use CraftCms\Cms\Form\Controls\Text;
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
        Select::class,
        Lightswitch::class,
    ];
}
