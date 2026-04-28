<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Extensions\DateTwigExtension;
use CraftCms\Cms\Twig\PageLifecycle;
use CraftCms\Cms\Twig\Twig;

beforeEach(function () {
    $this->pageLifecycle = app(PageLifecycle::class);
    $this->env = app(Twig::class)->create();
});

function dateFilterNames(array $filters): array
{
    return array_map(fn ($filter) => $filter->getName(), $filters);
}

function dateFunctionNames(array $functions): array
{
    return array_map(fn ($function) => $function->getName(), $functions);
}

describe('DateTwigExtension', function () {
    it('registers expected filters and functions', function () {
        $extension = new DateTwigExtension($this->pageLifecycle, $this->env);

        expect(dateFilterNames($extension->getFilters()))->toContain(
            'date',
            'datetime',
            'time',
            'atom',
            'httpdate',
            'timestamp',
        );
        expect(dateFunctionNames($extension->getFunctions()))->toContain('date');
    });

    it('handles empty timestamp values', function () {
        $extension = new DateTwigExtension($this->pageLifecycle, $this->env);

        expect($extension->timestampFilter(''))->toBe('');
        expect($extension->timestampFilter(null))->toBe('');
    });

    it('can convert dates via date function', function () {
        $extension = new DateTwigExtension($this->pageLifecycle, $this->env);
        $date = $extension->dateFunction($this->env, '2026-01-02');

        expect($date)->toBeInstanceOf(DateTimeInterface::class);
    });
});
