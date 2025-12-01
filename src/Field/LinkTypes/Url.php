<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\LinkTypes;

use craft\helpers\Cp;
use Exception;
use League\Uri\Uri;

use function CraftCms\Cms\t;

/**
 * URL link type.
 */
final class Url extends BaseTextLinkType
{
    public static function id(): string
    {
        return 'url';
    }

    #[\Override]
    public static function displayName(): string
    {
        return t('URL');
    }

    #[\Override]
    public function supports(string $value): bool
    {
        if (parent::supports($value)) {
            return true;
        }
        if (str_starts_with($value, '/')) {
            return true;
        }

        return str_starts_with($value, '#');
    }

    #[\Override]
    public function normalizeValue(string $value): string
    {
        $value = str_replace(' ', '+', $value);

        return parent::normalizeValue($value);
    }

    /**
     * @var bool Whether root-relative URLs should be allowed.
     *
     * @since 5.4.0
     */
    public bool $allowRootRelativeUrls = false;

    /**
     * @var bool Whether anchors should be allowed.
     *
     * @since 5.4.0
     */
    public bool $allowAnchors = false;

    /**
     * @return bool Whether custom URL schemes should be allowed.
     *
     * @since 5.7.0
     */
    public bool $allowCustomSchemes = false;

    protected function urlPrefix(): array
    {
        return ['https://', 'http://'];
    }

    public function getSettingsHtml(): string
    {
        return
            Cp::lightswitchFieldHtml([
                'label' => t('Allow root-relative URLs'),
                'name' => 'allowRootRelativeUrls',
                'on' => $this->allowRootRelativeUrls,
            ]).
            Cp::lightswitchFieldHtml([
                'label' => t('Allow anchors'),
                'name' => 'allowAnchors',
                'on' => $this->allowAnchors,
            ]).
            Cp::lightswitchFieldHtml([
                'label' => t('Allow custom URL schemes'),
                'name' => 'allowCustomSchemes',
                'on' => $this->allowCustomSchemes,
            ]);
    }

    #[\Override]
    protected function inputAttributes(): array
    {
        return [
            'type' => 'url',
            'inputmode' => 'url',
        ];
    }

    #[\Override]
    public function validateValue(string $value, ?string &$error = null): bool
    {
        try {
            // Leveraging Uri package to convert domains to punycode
            $value = Uri::new($value)->toString();

            return parent::validateValue($value, $error);
        } catch (Exception) {
            return false;
        }
    }

    #[\Override]
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
