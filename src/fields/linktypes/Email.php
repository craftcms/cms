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

    public static function label(): string
    {
        return Craft::t('app', 'Email');
    }

    protected static function urlPrefix(): string|array
    {
        return 'mailto:';
    }

    protected static function inputAttributes(): array
    {
        return [
            'type' => 'email',
            'inputmode' => 'email',
        ];
    }

    protected static function pattern(): string
    {
        $emailPattern = trim((new EmailValidator())->pattern, '/^$');
        return "^mailto:$emailPattern(\?.*)?$";
    }
}
