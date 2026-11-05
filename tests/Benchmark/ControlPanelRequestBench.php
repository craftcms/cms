<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Benchmark;

use Generator;
use PhpBench\Attributes as Bench;

/**
 * Full control panel page loads: booting the application, routing, authorization, building the page's props and
 * rendering the response.
 *
 * `editor` isn't an admin, so their pages also measure permission checks across every section and volume.
 */
#[Bench\Revs(1)]
#[Bench\Iterations(15)]
#[Bench\Groups(['request'])]
#[Bench\OutputTimeUnit('milliseconds', precision: 1)]
#[Bench\BeforeMethods('resolvePath')]
class ControlPanelRequestBench extends BenchCase
{
    private string $path;

    /** @param array{path: string, user: string} $params */
    public function resolvePath(array $params): void
    {
        // Entry IDs depend on the seed, so `{section}` stands for the section's first entry
        $this->path = preg_replace_callback(
            '/\{(\w+)\}/',
            fn (array $match) => (string) self::query(<<<SQL
                select min(e.id) from entries e
                join sections s on s.id = e.sectionId
                join elements el on el.id = e.id
                where s.handle = '{$match[1]}' and el.draftId is null and el.revisionId is null
                and el.canonicalId is null and el.dateDeleted is null
                SQL),
            $params['path'],
        );
    }

    /** @param array{path: string, user: string} $params */
    #[Bench\ParamProviders('pages')]
    public function benchPage(array $params): void
    {
        $this->request($this->path, $params['user']);
    }

    public function pages(): Generator
    {
        yield 'dashboard' => ['path' => 'dashboard', 'user' => 'craftcms'];
        yield 'entries index' => ['path' => 'content/entries', 'user' => 'craftcms'];
        yield 'entry form' => ['path' => 'entries/posts/{posts}', 'user' => 'craftcms'];
        yield 'entry form, every field type' => ['path' => 'entries/showcase/{showcase}', 'user' => 'craftcms'];
        yield 'entry form, Matrix' => ['path' => 'entries/matrixPlayground/{matrixPlayground}', 'user' => 'craftcms'];
        yield 'assets index' => ['path' => 'assets/images', 'user' => 'craftcms'];
        yield 'settings' => ['path' => 'settings', 'user' => 'craftcms'];
        yield 'fields' => ['path' => 'settings/fields', 'user' => 'craftcms'];
        yield 'edit field' => ['path' => 'settings/fields/edit/1', 'user' => 'craftcms'];
        yield 'editor: dashboard' => ['path' => 'dashboard', 'user' => 'editor'];
        yield 'editor: entries index' => ['path' => 'content/entries', 'user' => 'editor'];
        yield 'editor: entry form' => ['path' => 'entries/posts/{posts}', 'user' => 'editor'];
    }
}
