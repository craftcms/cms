<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\linktypes;

use Craft;
use craft\helpers\Cp;

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

    public function supports(string $value): bool
    {
        return parent::supports($value) || str_starts_with($value, '/') || str_starts_with($value, '#');
    }

    /**
     * @var bool Whether root-relative URLs should be allowed.
     * @since 5.4.0
     */
    public bool $allowRootRelativeUrls = true;

    /**
     * @var bool Whether anchors should be allowed.
     * @since 5.4.0
     */
    public bool $allowAnchors = true;

    protected function urlPrefix(): array
    {
        return ['https://', 'http://'];
    }

    public function getSettingsHtml(): ?string
    {
        return
            Cp::lightswitchFieldHtml([
                'label' => Craft::t('app', 'Allow root-relative URLs'),
                'name' => 'allowRootRelativeUrls',
                'on' => $this->allowRootRelativeUrls,
            ]) .
            Cp::lightswitchFieldHtml([
                'label' => Craft::t('app', 'Allow anchors'),
                'name' => 'allowAnchors',
                'on' => $this->allowAnchors,
            ]);
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
        $pattern = 'https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&\/\/=]*)';

        if ($this->allowRootRelativeUrls) {
            $pattern .= '|\/';
        }

        if ($this->allowAnchors) {
            $pattern .= '|#';
        }

        return "^($pattern)";
    }
}
