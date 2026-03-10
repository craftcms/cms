<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\linktypes;

use Craft;
use craft\helpers\Cp;
use League\Uri\Uri;

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

    public function normalizeValue(string $value): string
    {
        $value = str_replace(' ', '+', $value);
        return parent::normalizeValue($value);
    }

    /**
     * @var bool Whether root-relative URLs should be allowed.
     * @since 5.4.0
     */
    public bool $allowRootRelativeUrls = false;

    /**
     * @var bool Whether anchors should be allowed.
     * @since 5.4.0
     */
    public bool $allowAnchors = false;

    /**
     * @return bool Whether custom URL schemes should be allowed.
     * @since 5.7.0
     */
    public bool $allowCustomSchemes = false;

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
            ]) .
            Cp::lightswitchFieldHtml([
                'label' => Craft::t('app', 'Allow custom URL schemes'),
                'name' => 'allowCustomSchemes',
                'on' => $this->allowCustomSchemes,
            ]);
    }

    protected function inputAttributes(): array
    {
        return [
            'type' => 'url',
            'inputmode' => 'url',
        ];
    }

    public function validateValue(string $value, ?string &$error = null): bool
    {
        try {
            // Leveraging Uri package to convert domains to punycode
            $value = Uri::new($value);
            return parent::validateValue($value, $error);
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function pattern(): string
    {
        $pattern = 'https?:\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)*)(?::\d{1,5})?(?:$|[?\/#])';

        if ($this->allowRootRelativeUrls) {
            $pattern .= '|\/';
        }

        if ($this->allowAnchors) {
            $pattern .= '|#';
        }

        if ($this->allowCustomSchemes) {
            $pattern .= '|(?!https?:)\w+:.+';
        }

        return "^($pattern)";
    }
}
