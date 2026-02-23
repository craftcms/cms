<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Enums;

enum Position: int
{
    case Head = 1;
    case BodyBegin = 2;
    case BodyEnd = 3;
}
