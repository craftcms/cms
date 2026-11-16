<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Benchmark;

use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;

/**
 * Full control panel page loads: booting the application, routing, authorization, building the page's props and
 * rendering the response.
 *
 * `editor` isn't an admin, so their pages also measure permission checks across every section and volume.
 */
class ControlPanelRequestBench extends BenchCase
{
    private string $resolvedPath;

    #[Before]
    public function resolvePath(string $path): void
    {
        // Entry IDs depend on the seed, so `{section}` stands for the section's first entry
        $this->resolvedPath = preg_replace_callback(
            '/\{(\w+)\}/',
            fn (array $match) => (string) $this->query(<<<SQL
                select min(e.id) from entries e
                join sections s on s.id = e.sectionId
                join elements el on el.id = e.id
                where s.handle = '{$match[1]}' and el.draftId is null and el.revisionId is null
                and el.canonicalId is null and el.dateDeleted is null
                SQL),
            $path,
        );
    }

    #[DataProvider('pages')]
    public function benchPage(string $path, string $user): void
    {
        $this->request($this->resolvedPath, $user);
    }

    /**
     * @return iterable<string, array{string, string}> The page's path and the user who requests it
     */
    public static function pages(): iterable
    {
        yield 'dashboard' => ['dashboard', 'craftcms'];
        yield 'entries index' => ['content/entries', 'craftcms'];
        yield 'entry form' => ['entries/posts/{posts}', 'craftcms'];
        yield 'entry form, every field type' => ['entries/showcase/{showcase}', 'craftcms'];
        yield 'entry form, Matrix' => ['entries/matrixPlayground/{matrixPlayground}', 'craftcms'];
        yield 'assets index' => ['assets/images', 'craftcms'];
        yield 'settings' => ['settings', 'craftcms'];
        yield 'fields' => ['settings/fields', 'craftcms'];
        yield 'edit field' => ['settings/fields/edit/1', 'craftcms'];
        yield 'editor: dashboard' => ['dashboard', 'editor'];
        yield 'editor: entries index' => ['content/entries', 'editor'];
        yield 'editor: entry form' => ['entries/posts/{posts}', 'editor'];
    }
}
