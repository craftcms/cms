<?php

declare(strict_types=1);

namespace CraftCms\Cms\Structure\Enums;

enum Action: string
{
    case Prepend = 'prepend';
    case Append = 'append';
    case PlaceBefore = 'placeBefore';
    case PlaceAfter = 'placeAfter';
}
