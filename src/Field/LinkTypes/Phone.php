<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\LinkTypes;

use CraftCms\Cms\Field\Link;

use function CraftCms\Cms\t;

/**
 * Phone number link type.
 */
class Phone extends BaseTextLinkType
{
    public static function id(): string
    {
        return 'tel';
    }

    #[\Override]
    public static function displayName(): string
    {
        return t('Phone');
    }

    protected function urlPrefix(): string
    {
        return 'tel:';
    }

    #[\Override]
    public function renderValue(string $value): string
    {
        return str_replace(' ', '-', $value);
    }

    #[\Override]
    protected function inputAttributes(): array
    {
        return [
            'type' => 'tel',
            'inputmode' => 'tel',
        ];
    }

    #[\Override]
    protected function pattern(): string
    {
        return "^tel:[\d\+\(\)\-,; ]+$";
    }
}
