<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Extensions\ArrayTwigExtension;
use CraftCms\Cms\Twig\PageLifecycle;
use CraftCms\Cms\Twig\Twig;

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
        expect($extension->collectFunction([1, 2, 3]))->toBeInstanceOf(Illuminate\Support\Collection::class);
    });

    it('supports map/filter pipeline helpers', function () {
        $extension = new ArrayTwigExtension($this->pageLifecycle, $this->env);

        $filtered = $extension->filterFilter($this->env, [0, 1, 2, null]);
        $mapped = $extension->mapFilter($this->env, [1, 2, 3], fn (int $v) => $v * 2);

        expect(array_values($filtered))->toBe([1, 2]);
        expect($mapped)->toBe([2, 4, 6]);
    });
});
