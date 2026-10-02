<?php

declare(strict_types=1);

namespace CraftCms\Cms\Structure\Enums;

enum Mode: string
{
    case Insert = 'insert';
    case Update = 'update';
    case Auto = 'auto';
}
