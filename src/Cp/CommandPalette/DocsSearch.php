<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\CommandPalette;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Markdown;
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
 * Docs other than this version of Craft's, such as older versions' or
 * Commerce's, are left out.
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
     * Most of what an unscoped search returns is from older versions' docs,
     * which are left out, so this version's docs are searched on their own
     * too, and anything only that turns up follows the rest.
     *
     * @return list<CommandPaletteItem>|null
     */
    public function search(string $query): ?array
    {
        try {
            $results = Cache::remember(
                'commandPalette.docs.'.md5(mb_strtolower($query)),
                now()->addHour(),
                fn (): array => $this->fetch($query),
            );
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
                && ! $this->isFromOtherDocs($result['url'])
            ) {
                $items[$result['url']] ??= $this->item($result);
            }
        }

        return array_values($items);
    }

    /**
     * Everything the search turns up, then what it turns up in this version's
     * docs alone.
     *
     * @return array<mixed>
     */
    private function fetch(string $query): array
    {
        $request = fn (Pool $pool, array $params) => $pool
            ->acceptJson()
            ->connectTimeout(3)
            ->timeout(5)
            ->get(self::ENDPOINT, ['query' => $query, ...$params]);

        $responses = Http::pool(fn (Pool $pool) => [
            $request($pool, []),
            $request($pool, ['scopes' => sprintf('docs/%d.x', (int) Cms::VERSION)]),
        ]);

        $results = [];

        foreach ($responses as $response) {
            if (! $response instanceof Response) {
                throw $response;
            }

            array_push($results, ...(array) $response->throw()->json());
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

    /**
     * Whether a result is from docs other than this version of Craft's, such
     * as Craft 5's or Commerce's. The Knowledge Base and the glossary aren't
     * docs, and apply to any version.
     */
    private function isFromOtherDocs(string $url): bool
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        return str_starts_with($path, '/docs/')
            && ! str_starts_with($path, sprintf('/docs/%d.x/', (int) Cms::VERSION));
    }

    /** Whether a URL from craftcms.com is safe to link to. */
    private function isWebUrl(mixed $url): bool
    {
        return is_string($url) && Str::startsWith($url, ['https://', 'http://']);
    }
}
