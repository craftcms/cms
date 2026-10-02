<?php

declare(strict_types=1);

namespace CraftCms\Cms\Section\Enums;

use CraftCms\Cms\Cp\Concerns\CanSelect;

use function CraftCms\Cms\t;

enum DefaultPlacement: string
{
    use CanSelect;

    case Beginning = 'beginning';
    case End = 'end';

    public function label(): string
    {
        return match ($this) {
            self::Beginning => t('Before other {type}', ['type' => t('entries')]),
            self::End => t('After other {type}', ['type' => t('entries')]),
        };
    }
}
