<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Extensions\ArrayTwigExtension;
use CraftCms\Cms\Twig\Twig;
use CraftCms\Cms\View\PageLifecycle;
use Illuminate\Support\Collection;
use Twig\Error\RuntimeError;

beforeEach(function () {
    $this->pageLifecycle = app(PageLifecycle::class);
    $this->env = app(Twig::class)->create();
});

function arrayFilterNames(array $filters): array
{
    return array_map(fn ($filter) => $filter->getName(), $filters);
}

function arrayFunctionNames(array $functions): array
{
    return array_map(fn ($function) => $function->getName(), $functions);
}

describe('ArrayTwigExtension', function () {
    it('registers expected filters and functions', function () {
        $extension = new ArrayTwigExtension($this->pageLifecycle, $this->env);

        expect(arrayFilterNames($extension->getFilters()))->toContain(
            'filter',
            'group',
            'map',
            'reduce',
            'merge',
            'withoutKey',
        );
        expect(arrayFunctionNames($extension->getFunctions()))->toContain('collect', 'shuffle', 'combine');
    });

    it('provides array utility behavior', function () {
        $extension = new ArrayTwigExtension($this->pageLifecycle, $this->env);

        expect($extension->indexOfFilter('hello', 'll'))->toBe(2);
        expect($extension->withoutKeyFilter(['a' => 1, 'b' => 2], 'a'))->toBe(['b' => 2]);
        expect($extension->collectFunction([1, 2, 3]))->toBeInstanceOf(Collection::class);
    });

    it('supports map/filter pipeline helpers', function () {
        $extension = new ArrayTwigExtension($this->pageLifecycle, $this->env);

        $filtered = $extension->filterFilter($this->env, false, [0, 1, 2, null]);
        $mapped = $extension->mapFilter($this->env, false, [1, 2, 3], fn (int $v) => $v * 2);

        expect(array_values($filtered))->toBe([1, 2]);
        expect($mapped)->toBe([2, 4, 6]);
    });

    describe('whereFilter', function () {
        it('filters to elements matching the given key/value, preserving keys by default', function () {
            $extension = new ArrayTwigExtension($this->pageLifecycle, $this->env);

            $array = ['a' => ['status' => 'live'], 'b' => ['status' => 'draft'], 'c' => ['status' => 'live']];

            expect($extension->whereFilter(false, $array, 'status', 'live'))->toBe([
                'a' => ['status' => 'live'],
                'c' => ['status' => 'live'],
            ]);
        });

        it('reindexes the result when keepKeys is false', function () {
            $extension = new ArrayTwigExtension($this->pageLifecycle, $this->env);

            $array = ['a' => ['status' => 'live'], 'b' => ['status' => 'draft'], 'c' => ['status' => 'live']];

            expect($extension->whereFilter(false, $array, 'status', 'live', keepKeys: false))->toBe([
                ['status' => 'live'],
                ['status' => 'live'],
            ]);
        });

        it('respects strict comparison', function () {
            $extension = new ArrayTwigExtension($this->pageLifecycle, $this->env);

            $array = [['n' => 1], ['n' => '1'], ['n' => 2]];

            expect($extension->whereFilter(false, $array, 'n', 1, strict: false, keepKeys: false))->toBe([['n' => 1], ['n' => '1']]);
            expect($extension->whereFilter(false, $array, 'n', 1, strict: true, keepKeys: false))->toBe([['n' => 1]]);
        });

        it('defaults value to true', function () {
            $extension = new ArrayTwigExtension($this->pageLifecycle, $this->env);

            $array = [['enabled' => true], ['enabled' => false]];

            expect($extension->whereFilter(false, $array, 'enabled', keepKeys: false))->toBe([['enabled' => true]]);
        });

        it('throws when the key contains a "." while sandboxed', function () {
            $extension = new ArrayTwigExtension($this->pageLifecycle, $this->env);

            $extension->whereFilter(true, [], 'foo.bar');
        })->throws(RuntimeError::class);

        it('allows dotted keys when not sandboxed', function () {
            $extension = new ArrayTwigExtension($this->pageLifecycle, $this->env);

            expect($extension->whereFilter(false, [], 'foo.bar'))->toBe([]);
        });
    });
});
