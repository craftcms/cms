<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\LinkTypes;

use Override;

/**
 * Phone number link type.
 */
class Sms extends BaseTextLinkType
{
    public static function id(): string
    {
        return 'sms';
    }

    #[Override]
    public static function displayName(): string
    {
        return 'SMS';
    }

    protected function urlPrefix(): string
    {
        return 'sms:';
    }

    #[Override]
    public function normalizeValue(string $value): string
    {
        preg_match('/^([^?&]*)(?:[?&]+(.*))?$/', $value, $matches);
        $root = $matches[1];
        $qs = $matches[2] ?? null;
        $qs = str_replace(' ', '%20', $qs);
        $value = sprintf('%s%s', $root, $qs ? "&$qs" : '');

        return parent::normalizeValue($value);
    }

    #[Override]
    public function renderValue(string $value): string
    {
        return str_replace(' ', '-', $value);
    }

    /** @return array<string, string> */
    #[Override]
    protected function inputAttributes(): array
    {
        return [
            'type' => 'tel',
            'inputmode' => 'tel',
        ];
    }

    #[Override]
    protected function pattern(): string
    {
        return "^sms:[\d\+\(\)\-,; ]+([\?&].*)?$";
    }
}
