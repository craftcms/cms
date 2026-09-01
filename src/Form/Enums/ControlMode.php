<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Enums;

enum ControlMode: string
{
    case Editable = 'editable';
    case ReadOnly = 'readOnly';
    case Disabled = 'disabled';
}
