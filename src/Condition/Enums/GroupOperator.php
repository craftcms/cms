<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition\Enums;

enum GroupOperator: string
{
    case And = 'and';
    case Or = 'or';
}
