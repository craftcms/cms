<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

/**
 * Search helper.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Search} instead.
 */
class Search
{
    /**
     * Normalizes search keywords.
     *
     * @param string|string[] $str The dirty keywords
     * @param array $ignore Ignore words to strip out
     * @param bool $processCharMap Whether to remove punctuation and diacritics (default is true)
     * @param string|null $language The language that the character map should be based on, if `$processCharMap` is `true`.
     * @return string The cleansed keywords.
     */
    public static function normalizeKeywords(array|string $str, array $ignore = [], bool $processCharMap = true, ?string $language = null): string
    {
        return \CraftCms\Cms\Support\Search::normalizeKeywords($str, $ignore, $processCharMap, $language);
    }
}
