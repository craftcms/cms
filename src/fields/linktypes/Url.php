<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\linktypes;

use Craft;
use craft\fields\Link;
use craft\validators\UrlValidator;

/**
 * URL link type.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
abstract class Url extends BaseTextLinkType
{
    public static function id(): string
    {
        return 'url';
    }

    public static function label(): string
    {
        return Craft::t('app', 'URL');
    }

    protected static function urlPrefix(): array
    {
        return ['https://', 'http://'];
    }

    protected static function inputAttributes(): array
    {
        return [
            'type' => 'url',
            'inputmode' => 'url',
        ];
    }

    protected static function pattern(): string
    {
        return str_replace(UrlValidator::URL_PATTERN, '{schemes}', '(https|http)');
    }
}
