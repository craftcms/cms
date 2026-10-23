<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\CommandPalette\SearchProviders;

use CraftCms\Cms\Cp\CommandPalette\CommandPaletteItem;
use CraftCms\Cms\Cp\CommandPalette\Contracts\SearchProvider;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

use function CraftCms\Cms\t;

/**
 * Finds terms in the Craft CMS glossary on craftcms.com, so the palette can
 * explain what something is without leaving the control panel.
 *
 * The whole glossary is fetched once and cached, then searched here, so what
 * people type in the palette never leaves the site.
 *
 * @since 6.0.0
 */
readonly class GlossarySearchProvider implements SearchProvider
{
    /** Where the glossary is listed, every term at once. */
    public const string ENDPOINT = 'https://craftcms.com/api/glossary';

    private const string CACHE_KEY = 'commandPalette.glossary';

    public function getHandle(): string
    {
        return 'glossary';
    }

    public function getLabel(): string
    {
        return t('Glossary');
    }

    public function isAvailable(CraftUser $user): bool
    {
        return true;
    }

    public function search(string $query, CraftUser $user): iterable
    {
        foreach ($this->terms() as $term) {
            $item = $this->item($term);

            if ($item->matches($query)) {
                yield $item;
            }
        }
    }

    /**
     * The glossary's terms, fetched at most once a day. When craftcms.com
     * can't be reached, none for a few minutes, rather than having every
     * search wait on it.
     *
     * @return list<array<string, mixed>>
     */
    private function terms(): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout(3)
                ->timeout(5)
                ->get(self::ENDPOINT)
                ->throw();

            $terms = array_values(array_filter(
                (array) $response->json(),
                fn (mixed $term): bool => is_array($term)
                    && is_string($term['title'] ?? null)
                    && is_string($term['slug'] ?? null),
            ));

            Cache::put(self::CACHE_KEY, $terms, now()->addDay());
        } catch (Throwable $e) {
            Log::warning('Couldn’t fetch the Craft CMS glossary: '.$e->getMessage(), [__METHOD__]);
            $terms = [];
            Cache::put(self::CACHE_KEY, $terms, now()->addMinutes(10));
        }

        return $terms;
    }

    /** @param array<string, mixed> $term */
    private function item(array $term): CommandPaletteItem
    {
        $docsLinks = array_values(array_filter(
            array_map(
                fn (mixed $link): ?array => is_array($link) && is_string($link['title'] ?? null) && $this->isWebUrl($link['url'] ?? null)
                    ? ['title' => $link['title'], 'url' => $link['url']]
                    : null,
                (array) ($term['docsLinks'] ?? []),
            ),
        ));

        return new CommandPaletteItem()
            ->id("glossary-{$term['slug']}")
            ->label($term['title'])
            ->description(t('Glossary'))
            ->icon('book')
            ->view([
                'type' => 'glossary',
                'definition' => is_string($term['summaryPlain'] ?? null) ? trim($term['summaryPlain']) : '',
                'url' => $this->isWebUrl($term['url'] ?? null) ? $term['url'] : null,
                'docsLinks' => $docsLinks,
            ]);
    }

    /** Whether a URL from the glossary is safe to link to. */
    private function isWebUrl(mixed $url): bool
    {
        return is_string($url) && Str::startsWith($url, ['https://', 'http://']);
    }
}
