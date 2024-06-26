<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\linktypes;

use Craft;
use craft\fields\Link;

/**
 * Phone number link type.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
class Phone extends BaseTextLinkType
{
    public static function id(): string
    {
        return 'phone';
    }

    public static function label(): string
    {
        return Craft::t('app', 'Phone');
    }

    protected static function urlPrefix(): string|array
    {
        return 'tel:';
    }

    public static function normalize(string $value): string
    {
        $value = str_replace(' ', '-', $value);
        return parent::normalize($value);
    }

    protected static function inputAttributes(): array
    {
        return [
            'type' => 'tel',
            'inputmode' => 'tel',
        ];
    }

    protected static function pattern(): string
    {
        return "^tel:[\d\+\(\)\-,;]+$";
    }
}
