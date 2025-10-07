<?php

namespace CraftCms\Cms\Field\LinkTypes;

use Craft;
use CraftCms\Cms\Field\Link;

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
        return Craft::t('app', 'Phone');
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
