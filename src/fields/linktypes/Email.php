<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\linktypes;

use Craft;
use craft\fields\Link;
use yii\validators\EmailValidator;

/**
 * Email link type.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
class Email extends BaseTextLinkType
{
    public static function id(): string
    {
        return 'email';
    }

    public static function displayName(): string
    {
        return Craft::t('app', 'Email');
    }

    protected function urlPrefix(): string|array
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
        $emailPattern = trim((new EmailValidator())->pattern, '/^$');
        return "^mailto:$emailPattern(\?.*)?$";
    }
}
