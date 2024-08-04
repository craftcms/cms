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
        return 'tel';
    }

    public static function displayName(): string
    {
        return Craft::t('app', 'Phone');
    }

    protected function urlPrefix(): string|array
    {
        return 'tel:';
    }

    public function normalizeValue(string $value): string
    {
        $value = str_replace(' ', '-', $value);
        return parent::normalizeValue($value);
    }

    protected function inputAttributes(): array
    {
        return [
            'type' => 'tel',
            'inputmode' => 'tel',
        ];
    }

    protected function pattern(): string
    {
        return "^tel:[\d\+\(\)\-,;]+$";
    }
}
