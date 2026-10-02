<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Enums;

/**
 * Semantic variants shared across the CP component system. Mirrors the
 * `Variant` constants in `@craftcms/cp` (src/constants/variants.ts).
 */
enum Variant: string
{
    case Neutral = 'neutral';
    case Success = 'success';
    case Warning = 'warning';
    case Danger = 'danger';
    case Info = 'info';
}
