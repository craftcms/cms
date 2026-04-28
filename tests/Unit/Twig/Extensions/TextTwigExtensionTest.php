<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Extensions\TextTwigExtension;
use CraftCms\Cms\Twig\PageLifecycle;
use CraftCms\Cms\Twig\Twig;

beforeEach(function () {
    $this->pageLifecycle = app(PageLifecycle::class);
    $this->env = app(Twig::class)->create();
});

function textFilterNames(array $filters): array
{
    return array_map(fn ($filter) => $filter->getName(), $filters);
}

function textFunctionNames(array $functions): array
{
    return array_map(fn ($function) => $function->getName(), $functions);
}

describe('TextTwigExtension', function () {
    it('registers expected filters and functions', function () {
        $extension = new TextTwigExtension($this->pageLifecycle, $this->env);

        expect(textFilterNames($extension->getFilters()))->toContain(
            'truncate',
            'camel',
            'pascal',
            'snake',
            'replace',
            'hash',
        );
        expect(textFilterNames($extension->getFilters()))->not->toContain('ucfirst', 'ucwords');
        expect(textFunctionNames($extension->getFunctions()))->toContain('randomString', 'uuid', 'uuid7');
    });

    it('supports text transforms', function () {
        $extension = new TextTwigExtension($this->pageLifecycle, $this->env);

        expect($extension->camelFilter('foo bar'))->toBe('fooBar');
        expect($extension->pascalFilter('foo bar'))->toBe('FooBar');
        expect($extension->snakeFilter('foo bar'))->toBe('foo_bar');
        expect($extension->truncateFilter('Hello world', 8))->toStartWith('Hello');
    });

    it('supports replace and hash helpers', function () {
        $extension = new TextTwigExtension($this->pageLifecycle, $this->env);

        expect($extension->replaceFilter('a-b-c', '-', '_'))->toBe('a_b_c');
        expect($extension->replaceFilter('a1b2', '/\d/', '#', true))->toBe('a#b#');
        expect($extension->hashFilter('abc', 'sha256'))->toBe(hash('sha256', 'abc'));
    });
});
