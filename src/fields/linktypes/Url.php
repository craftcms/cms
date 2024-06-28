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
class Url extends BaseTextLinkType
{
    public static function id(): string
    {
        return 'url';
    }

    public static function displayName(): string
    {
        return Craft::t('app', 'URL');
    }

    protected function urlPrefix(): array
    {
        return ['https://', 'http://'];
    }

    protected function inputAttributes(): array
    {
        return [
            'type' => 'url',
            'inputmode' => 'url',
        ];
    }

    protected function pattern(): string
    {
        return str_replace(UrlValidator::URL_PATTERN, '{schemes}', '(https|http)');
    }
}
