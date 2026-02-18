<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\linktypes;

use craft\fields\Link;

/**
 * Phone number link type.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.7.0
 */
class Sms extends BaseTextLinkType
{
    public static function id(): string
    {
        return 'sms';
    }

    public static function displayName(): string
    {
        return 'SMS';
    }

    protected function urlPrefix(): string|array
    {
        return 'sms:';
    }

    public function normalizeValue(string $value): string
    {
        preg_match('/^([^?&]*)(?:[?&]+(.*))?$/', $value, $matches);
        $root = $matches[1];
        $qs = $matches[2] ?? null;
        $qs = str_replace(' ', '%20', $qs);
        $value = sprintf('%s%s', $root, $qs ? "&$qs" : '');
        return parent::normalizeValue($value);
    }

    public function renderValue(string $value): string
    {
        return str_replace(' ', '-', $value);
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
        return "^sms:[\d\+\(\)\-,; ]+([\?&].*)?$";
    }
}
