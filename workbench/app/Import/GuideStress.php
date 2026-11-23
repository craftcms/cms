<?php

declare(strict_types=1);

namespace Workbench\App\Import;

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Support\Json;

/**
 * Hundreds of guides with every field filled in, for seeing how a long run holds up. They're
 * varied from the complete guides, and written out by the seeder rather than kept in the repo.
 * Each has its own slug, which the others relate to it by.
 */
final class GuideStress
{
    public const string SOURCE = '@craftcms/workbench/resources/import/guides-stress.json';

    public const int COUNT = 500;

    /** The batch size of its step; relations only reach guides at least one batch back, which exist by then. */
    public const int BATCH_SIZE = 25;

    private const string TEMPLATE_SOURCE = '@craftcms/workbench/resources/import/guides-complete.json';

    private const array AUTHORS = ['craftcms', 'ada'];

    private const array REVIEWERS = ['ada@example.com', 'support@craftcms.com'];

    private const array TOPICS = ['content-modeling', 'templating', 'importing', 'performance', 'plugins'];

    private const array DIFFICULTIES = ['beginner', 'intermediate', 'advanced'];

    /** Guides the seeder makes, which any generated guide can relate to. */
    private const array SEEDED_GUIDES = ['getting-started-with-craft', 'content-modeling-basics'];

    /** Writes the guides out, unless they're already there. */
    public static function write(): void
    {
        $path = Aliases::get(self::SOURCE);

        if (is_file($path)) {
            return;
        }

        /** @var list<array<string, mixed>> $templates */
        $templates = Json::decode((string) file_get_contents(Aliases::get(self::TEMPLATE_SOURCE)));
        $guides = [];
        $slugs = [];

        for ($i = 1; $i <= self::COUNT; $i++) {
            $guide = $templates[($i - 1) % count($templates)];
            $title = sprintf('%s, part %d', $guide['title'], $i);
            $slugs[$i] = sprintf('guide-stress-%04d', $i);

            $guides[] = [
                ...$guide,
                'title' => $title,
                'slug' => $slugs[$i],
                'postDate' => date('Y-m-d H:i:s', strtotime('2026-01-01 09:00:00') + $i * 3600 * 7),
                'enabled' => $i % 10 !== 0,
                // a guide has one author
                'authors' => [self::AUTHORS[$i % 2]],
                'summary' => sprintf('%s (Part %d of %d.)', $guide['summary'], $i, self::COUNT),
                'difficulty' => self::DIFFICULTIES[$i % 3],
                'featured' => $i % 7 === 0,
                'readingTime' => 5 + $i % 40,
                'topics' => array_values(array_unique([self::TOPICS[$i % 5], self::TOPICS[($i * 3) % 5]])),
                'reviewedOn' => date('Y-m-d', strtotime('2026-02-01') + $i * 86400),
                'reviewers' => [self::REVIEWERS[$i % 2]],
                'related' => self::related($i, $slugs),
                'content' => self::numbered($guide['content'], $i),
            ];
        }

        file_put_contents($path, Json::encode($guides, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * A seeded guide, and a couple from at least a batch back.
     *
     * @param  array<int, string>  $slugs
     * @return list<string>
     */
    private static function related(int $i, array $slugs): array
    {
        $related = [self::SEEDED_GUIDES[$i % 2]];

        foreach ([self::BATCH_SIZE, self::BATCH_SIZE * 2 + 3] as $back) {
            if (isset($slugs[$i - $back])) {
                $related[] = $slugs[$i - $back];
            }
        }

        return $related;
    }

    /**
     * The guide's content blocks, with headings that say which part they're from.
     *
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    private static function numbered(array $blocks, int $i): array
    {
        return array_map(function (array $block) use ($i): array {
            if (isset($block['heading'])) {
                $block['heading'] = sprintf('%s (part %d)', $block['heading'], $i);
            }

            if (isset($block['title'])) {
                $block['title'] = sprintf('%s (part %d)', $block['title'], $i);
            }

            return $block;
        }, $blocks);
    }
}
