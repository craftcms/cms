<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\HtmlSanitizer\AttributeSanitizers;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\TextSanitizer\UrlSanitizer;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

class VideoEmbedUrlSanitizer implements AttributeSanitizerInterface
{
    public function __construct(
        private readonly ?string $safeIframeRegexp = null,
    ) {}

    public function getSupportedElements(): ?array
    {
        return ['div', 'oembed'];
    }

    public function getSupportedAttributes(): ?array
    {
        return ['data-oembed-url', 'url'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        if ($this->safeIframeRegexp === null) {
            return UrlSanitizer::sanitize(
                $value,
                $config->getAllowedMediaSchemes(),
                $config->getForceHttpsUrls(),
                $config->getAllowedMediaHosts(),
                $config->getAllowRelativeMedias(),
            );
        }
        if (@preg_match($this->safeIframeRegexp, $value) !== 1) {
            return null;
        }

        return UrlSanitizer::sanitize(
            $value,
            $config->getAllowedMediaSchemes(),
            $config->getForceHttpsUrls(),
            $config->getAllowedMediaHosts(),
            $config->getAllowRelativeMedias(),
        );
    }
}
