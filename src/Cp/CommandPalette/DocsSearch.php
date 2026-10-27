<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\CommandPalette;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Markdown;
use CraftCms\Cms\Support\Facades\Plugins;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

use function CraftCms\Cms\t;

/**
 * Searches the Craft CMS documentation, Knowledge Base, and glossary on
 * craftcms.com, for the palette's “Search Craft CMS documentation” view.
 * Only this major version of Craft's docs are searched, along with the
 * installed major version of Commerce's, if Commerce is installed.
 *
 * What's typed there is sent to craftcms.com, so it's only searched from that
 * view, never as part of the palette's own search.
 *
 * @since 6.0.0
 */
readonly class DocsSearch
{
    public const string ENDPOINT = 'https://craftcms.com/api/docs/search';

    /**
     * The results for the query, best first, or null if craftcms.com couldn't
     * be searched.
     *
     * @return list<CommandPaletteItem>|null
     */
    public function search(string $query): ?array
    {
        $versions = array_filter([
            'cms' => (int) Cms::VERSION,
            'commerce' => (int) Plugins::getPlugin('commerce')?->version,
        ]);
        $cacheKey = 'commandPalette.docs.'.md5(mb_strtolower($query).json_encode($versions));

        try {
            $results = Cache::get($cacheKey) ?? $this->fetch($query, $versions, $cacheKey);
        } catch (Throwable $e) {
            Log::warning('Couldn’t search the Craft CMS documentation: '.$e->getMessage(), [__METHOD__]);

            return null;
        }

        $items = [];

        foreach ($results as $result) {
            if (
                is_array($result)
                && is_string($result['title'] ?? null)
                && $this->isWebUrl($result['url'] ?? null)
            ) {
                $items[$result['url']] ??= $this->item($result);
            }
        }

        return array_values($items);
    }

    /**
     * Searches each product's docs for its installed major version: Craft's,
     * with the Knowledge Base and glossary, and Commerce's if it's installed.
     *
     * The API ranks each product's results but doesn't score them, so they
     * can't be sorted against each other, and are taken in turns instead.
     * If Commerce's docs can't be searched, Craft's results are still
     * returned, but not cached.
     *
     * @param  array<string, int>  $versions  Major versions, by product
     * @return array<mixed>
     */
    private function fetch(string $query, array $versions, string $cacheKey): array
    {
        $responses = Http::pool(fn (Pool $pool) => array_map(
            fn (string $product, int $version) => $pool
                ->as($product)
                ->acceptJson()
                ->connectTimeout(3)
                ->timeout(5)
                ->get(self::ENDPOINT, array_filter([
                    'query' => $query,
                    'product' => $product,
                    'version' => $version,
                    'includeHelp' => $product === 'cms' ? 1 : null,
                ])),
            array_keys($versions),
            $versions,
        ));

        $lists = [];
        $complete = true;

        foreach ($responses as $product => $response) {
            try {
                if (! $response instanceof Response) {
                    throw $response;
                }

                $lists[] = array_values((array) $response->throw()->json());
            } catch (Throwable $e) {
                if ($product === 'cms') {
                    throw $e;
                }

                Log::warning("Couldn’t search the $product documentation: ".$e->getMessage(), [__METHOD__]);
                $complete = false;
            }
        }

        $results = count($lists) > 1
            ? array_values(array_filter(array_merge(...array_map(null, ...$lists))))
            : $lists[0];

        if ($complete) {
            Cache::put($cacheKey, $results, now()->addHour());
        }

        return $results;
    }

    /** @param array<string, mixed> $result */
    private function item(array $result): CommandPaletteItem
    {
        [$title, $titleHtml] = $this->inlineMarkdown($result['title']);
        [$summary, $summaryHtml] = is_string($result['summaryPlain'] ?? null)
            ? $this->inlineMarkdown($result['summaryPlain'])
            : ['', ''];

        // The plain summary loses its code formatting, which the HTML one keeps.
        if ($summary !== '' && is_string($result['summaryHtml'] ?? null)) {
            $summaryHtml = $this->inlineHtml($result['summaryHtml']);
        }

        return new CommandPaletteItem()
            ->id('docs-'.md5($result['url']))
            ->label($title)
            ->labelHtml($titleHtml)
            ->hint($summary !== '' ? $summary : null)
            ->hintHtml($summary !== '' ? $summaryHtml : null)
            ->description($this->kind($result['type'] ?? null))
            ->icon('book')
            ->href($result['url'])
            ->external();
    }

    /**
     * Titles and summaries are Markdown, so `craft.entries` and the like show
     * up as code. Returns the text, and its HTML with only inline formatting.
     * Links are left as their text, as each result is a link itself.
     *
     * @return array{string, string}
     */
    private function inlineMarkdown(string $markdown): array
    {
        $markdown = trim((string) preg_replace(
            ['/!?\[([^\]]*)\]\([^)]*\)/', '/\s+/'],
            ['$1', ' '],
            $markdown,
        ));
        // Encoded first so any HTML in it stays text; Markdown then encodes it again.
        $html = strip_tags(Html::decodeDoubles(Markdown::parseParagraph(Html::encode($markdown))), '<code><em><strong>');

        return [Html::decode(strip_tags($html)), $html];
    }

    /**
     * HTML from craftcms.com, reduced to inline formatting without
     * attributes. Links are left as their text, as each result is a link
     * itself.
     */
    private function inlineHtml(string $html): string
    {
        return trim((string) preg_replace(
            ['/<(code|em|strong)\b[^>]*>/i', '/\s+/'],
            ['<$1>', ' '],
            strip_tags($html, '<code><em><strong>'),
        ));
    }

    /** What kind of page a result is: a glossary term, an article, or which part of the docs. */
    private function kind(mixed $type): ?string
    {
        return match ($type) {
            'Term' => t('Glossary'),
            'Knowledge Base Article' => t('Knowledge Base'),
            default => is_string($type) && $type !== '' ? $type : null,
        };
    }

    /** Whether a URL from craftcms.com is safe to link to. */
    private function isWebUrl(mixed $url): bool
    {
        return is_string($url) && Str::startsWith($url, ['https://', 'http://']);
    }
}
