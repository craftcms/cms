<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\anchors;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Anchors;

/**
 * Anchors parser
 *
 * Its settings are those of the `anchors` config setting, so changing them affects every `|anchors` filter call.
 *
 * @property string|null $anchorClass
 * @property string $anchorLinkPosition
 * @property string|null $anchorLinkClass
 * @property string $anchorLinkText
 * @property string $anchorLinkTitleText
 * @property bool $useAdditionalTagToAnchorTo
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Facades\Anchors} and the `anchors` config setting instead.
 */
class Parser
{
    private const SETTINGS = [
        'anchorClass',
        'anchorLinkPosition',
        'anchorLinkClass',
        'anchorLinkText',
        'anchorLinkTitleText',
        'useAdditionalTagToAnchorTo',
    ];

    public function __get(string $name): mixed
    {
        return in_array($name, self::SETTINGS, true) ? Cms::config()->anchors->{$name} : null;
    }

    public function __set(string $name, mixed $value): void
    {
        if (in_array($name, self::SETTINGS, true)) {
            Cms::config()->anchors->{$name}($value);
        }
    }

    public function __isset(string $name): bool
    {
        return in_array($name, self::SETTINGS, true) && isset(Cms::config()->anchors->{$name});
    }

    /**
     * Parses some HTML for headings and adds anchor links to them.
     *
     * @param string|string[] $tags The tags to add anchor links to.
     */
    public function parseHtml(string $html, array|string $tags = 'h1,h2,h3', ?string $language = null, bool $lowercase = false): string
    {
        return Anchors::parseHtml($html, $tags, $language, $lowercase);
    }

    /**
     * Generates an anchor name based on a given heading.
     */
    public function generateAnchorName(string $heading, ?string $language = null, bool $lowercase = false): string
    {
        return Anchors::generateAnchorName($heading, $language, $lowercase);
    }
}
