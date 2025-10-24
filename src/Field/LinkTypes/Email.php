<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\LinkTypes;

use CraftCms\Cms\Field\Link;
use yii\validators\EmailValidator;

use function CraftCms\Cms\t;

/**
 * Email link type.
 */
final class Email extends BaseTextLinkType
{
    public static function id(): string
    {
        return 'email';
    }

    #[\Override]
    public static function displayName(): string
    {
        return t('Email');
    }

    protected function urlPrefix(): string
    {
        return 'mailto:';
    }

    #[\Override]
    public function normalizeValue(string $value): string
    {
        $value = str_replace(' ', '+', $value);

        return parent::normalizeValue($value);
    }

    #[\Override]
    protected function inputAttributes(): array
    {
        return [
            'type' => 'email',
            'inputmode' => 'email',
        ];
    }

    #[\Override]
    protected function pattern(): string
    {
        $emailPattern = trim((new EmailValidator)->pattern, '/^$');

        return "^mailto:$emailPattern(\?.*)?$";
    }
}
