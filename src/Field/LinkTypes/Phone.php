<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\LinkTypes;

use CraftCms\Cms\Field\Link;

use function CraftCms\Cms\t;

/**
 * Phone number link type.
 */
final class Phone extends BaseTextLinkType
{
    public static function id(): string
    {
        return 'tel';
    }

    public static function displayName(): string
    {
        return t('Phone');
    }

    protected function urlPrefix(): string
    {
        return 'tel:';
    }

    public function renderValue(string $value): string
    {
        return str_replace(' ', '-', $value);
    }

    protected function inputAttributes(): array
    {
        return [
            'type' => 'tel',
            'inputmode' => 'tel',
        ];
    }

    protected function pattern(): string
    {
        return "^tel:[\d\+\(\)\-,; ]+$";
    }
}
