<?php

namespace CraftCms\Cms\Field\LinkTypes;

use Craft;
use CraftCms\Cms\Field\Link;
use yii\validators\EmailValidator;

/**
 * Email link type.
 *
 * @since 6.0.0
 */
final class Email extends BaseTextLinkType
{
    public static function id(): string
    {
        return 'email';
    }

    public static function displayName(): string
    {
        return Craft::t('app', 'Email');
    }

    protected function urlPrefix(): string
    {
        return 'mailto:';
    }

    public function normalizeValue(string $value): string
    {
        $value = str_replace(' ', '+', $value);

        return parent::normalizeValue($value);
    }

    protected function inputAttributes(): array
    {
        return [
            'type' => 'email',
            'inputmode' => 'email',
        ];
    }

    protected function pattern(): string
    {
        $emailPattern = trim((new EmailValidator)->pattern, '/^$');

        return "^mailto:$emailPattern(\?.*)?$";
    }
}
