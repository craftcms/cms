<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\linktypes;

use Craft;

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
        // Based on yii\validators\UrlValidator::$pattern
        return '^https?:\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(?::\d{1,5})?(?:$|[?\/#])';
    }
}
