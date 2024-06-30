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
        // Don't use the URL validator's pattern, as that doesn't require a TLD
        return 'https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&\/\/=]*)';
    }
}
