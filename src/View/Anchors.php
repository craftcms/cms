<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\AnchorsConfig;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use Illuminate\Container\Attributes\Singleton;
use InvalidArgumentException;

use function CraftCms\Cms\t;

/**
 * Adds linkable anchors to HTML headings.
 *
 * Anchor names are derived from the heading text, and the anchor links are configured by the
 * <config6:anchors> config setting.
 *
 * @since 6.0.0
 */
#[Singleton]
class Anchors
{
    /**
     * Parses some HTML for headings and adds anchor links to them.
     *
     * @param  string|list<string>  $tags  The tags to add anchor links to, as an array or a comma-separated string.
     * @param  string|null  $language  The content language, used when converting non-ASCII characters to ASCII.
     * @param  bool  $lowercase  Whether to always lowercase the entire anchor name.
     */
    public function parseHtml(string $html, array|string $tags = 'h1,h2,h3', ?string $language = null, bool $lowercase = false): string
    {
        $config = Cms::config()->anchors;

        if (is_string($tags)) {
            $tags = array_values(array_filter(array_map(trim(...), explode(',', $tags))));
        }

        $tagPattern = implode('|', array_map(fn (string $tag) => preg_quote($tag, '/'), $tags));

        return preg_replace_callback("/<($tagPattern)([^>]*)>\s*([\w\W]+?)\s*<\/\\1>/", function (array $match) use ($config, $language, $lowercase) {
            [, $tag, $attributes, $content] = $match;

            // Reuse the heading’s own `id`, unless the anchor goes on a separate tag
            $headingId = $config->useAdditionalTagToAnchorTo ? null : $this->headingId("<$tag$attributes>");
            $anchorName = $headingId ?? $this->generateAnchorName($content, $language, $lowercase);

            $heading = preg_replace('/\s+/', ' ', strip_tags(str_replace(['&nbsp;', "\u{00A0}"], ' ', $content)));
            $title = t($config->anchorLinkTitleText, ['heading' => $heading], 'site');
            $link = Html::tag('a', $config->anchorLinkText, [
                'class' => $config->anchorLinkClass,
                'title' => $title,
                'aria-label' => $title,
                'href' => "#$anchorName",
            ]);

            $anchorTag = $config->useAdditionalTagToAnchorTo
                ? Html::tag('span', '', ['class' => $config->anchorClass, 'id' => $anchorName])
                : '';
            $idAttribute = ! $config->useAdditionalTagToAnchorTo && $headingId === null ? " id=\"$anchorName\"" : '';
            $innerHtml = $config->anchorLinkPosition === AnchorsConfig::POS_BEFORE ? "$link $content" : "$content $link";

            return "$anchorTag<$tag$attributes$idAttribute>$innerHtml</$tag>";
        }, $html);
    }

    /**
     * Generates an anchor name from some heading text.
     *
     * @param  string|null  $language  The content language, used when converting non-ASCII characters to ASCII.
     * @param  bool  $lowercase  Whether to always lowercase the entire anchor name.
     */
    public function generateAnchorName(string $heading, ?string $language = null, bool $lowercase = false): string
    {
        $heading = htmlspecialchars_decode($heading, ENT_QUOTES);

        // Remove HTML tags and parenthesized text
        $heading = preg_replace(['/<(.*?)>/', '/\(.*?\)/'], '', $heading);

        // Remove inner-word punctuation
        $heading = preg_replace('/[\'"‘’“”]/u', '', $heading);

        $heading = str_replace(['&nbsp;', "\u{00A0}"], ' ', $heading);

        // Get the unicode letter and number “words”
        preg_match_all('/[\p{L}\p{N}]+/u', $heading, $matches);

        // Fully lowercase words that are all uppercase, and lowercase the first letter of the rest
        $words = array_map(
            fn (string $word) => $lowercase || strtoupper($word) === $word ? strtolower($word) : lcfirst($word),
            $matches[0],
        );

        return Str::ascii(implode('-', $words), $language ?? app()->getLocale());
    }

    private function headingId(string $openingTag): ?string
    {
        try {
            $id = Html::parseTagAttributes($openingTag, decode: true)['id'] ?? null;
        } catch (InvalidArgumentException) {
            return null;
        }

        return is_string($id) && $id !== '' ? $id : null;
    }
}
