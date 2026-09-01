<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Enums;

enum ChoicePresentation: string
{
    case Buttons = 'buttons';
    case Checkboxes = 'checkboxes';
    case Radios = 'radios';
    case Select = 'select';
}
