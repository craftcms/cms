<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Enums;

enum ElementActivityType: string
{
    case View = 'view';
    case Edit = 'edit';
    case Save = 'save';
}
