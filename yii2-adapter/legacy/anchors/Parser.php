<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\anchors;

use CraftCms\Cms\Support\Facades\Anchors;

/**
 * Anchors parser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Facades\Anchors} instead.
 */
class Parser
{
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
