<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\LinkTypes;

use Override;

use function CraftCms\Cms\t;

class Email extends BaseTextLinkType
{
    public static function id(): string
    {
        return 'email';
    }

    #[Override]
    public static function displayName(): string
    {
        return t('Email');
    }

    protected function urlPrefix(): string
    {
        return 'mailto:';
    }

    #[Override]
    public function normalizeValue(string $value): string
    {
        $value = str_replace(' ', '+', $value);

        return parent::normalizeValue($value);
    }

    /** @return array<string, string> */
    #[Override]
    protected function inputAttributes(): array
    {
        return [
            'type' => 'email',
            'inputmode' => 'email',
        ];
    }

    #[Override]
    protected function pattern(): string
    {
        $emailPattern = trim('/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/', '/^$');

        return "^mailto:$emailPattern(\?.*)?$";
    }
}
