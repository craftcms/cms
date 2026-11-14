<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Extensions\ArrayTwigExtension;
use CraftCms\Cms\Twig\Twig;
use CraftCms\Cms\View\PageLifecycle;
use CraftCms\Cms\View\TemplateManager;
use Illuminate\Support\Collection;
use Twig\Error\Error as TwigError;
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

    describe('groupFilter', function () {
        it('groups by a property name string', function () {
            $extension = new ArrayTwigExtension($this->pageLifecycle, $this->env);

            $array = [['status' => 'live'], ['status' => 'draft'], ['status' => 'live']];

            expect($extension->groupFilter(false, $array, 'status'))->toBe([
                'live' => [['status' => 'live'], ['status' => 'live']],
                'draft' => [['status' => 'draft']],
            ]);
        });

        it('groups using a closure arrow when sandboxed', function () {
            $extension = new ArrayTwigExtension($this->pageLifecycle, $this->env);

            $array = [1, 2, 3, 4];

            expect($extension->groupFilter(true, $array, fn (int $v) => $v % 2 === 0 ? 'even' : 'odd'))->toBe([
                'odd' => [1, 3],
                'even' => [2, 4],
            ]);
        });

        it('throws when the arrow is a non-Closure callable while sandboxed', function () {
            $extension = new ArrayTwigExtension($this->pageLifecycle, $this->env);

            // a first-class callable (`...`) is itself a Closure, so use an array callable to get a genuinely non-Closure callable
            $extension->groupFilter(true, [1, 2], [$extension, 'indexOfFilter']);
        })->throws(RuntimeError::class);
    });

    describe('whereFilter', function () {
        it('filters to elements matching the given key/value, preserving keys by default', function () {
            $extension = new ArrayTwigExtension($this->pageLifecycle, $this->env);

            $array = ['a' => ['status' => 'live'], 'b' => ['status' => 'draft'], 'c' => ['status' => 'live']];

            expect($extension->whereFilter($this->env, false, $array, 'status', 'live'))->toBe([
                'a' => ['status' => 'live'],
                'c' => ['status' => 'live'],
            ]);
        });

        it('reindexes the result when keepKeys is false', function () {
            $extension = new ArrayTwigExtension($this->pageLifecycle, $this->env);

            $array = ['a' => ['status' => 'live'], 'b' => ['status' => 'draft'], 'c' => ['status' => 'live']];

            expect($extension->whereFilter($this->env, false, $array, 'status', 'live', keepKeys: false))->toBe([
                ['status' => 'live'],
                ['status' => 'live'],
            ]);
        });

        it('respects strict comparison', function () {
            $extension = new ArrayTwigExtension($this->pageLifecycle, $this->env);

            $array = [['n' => 1], ['n' => '1'], ['n' => 2]];

            expect($extension->whereFilter($this->env, false, $array, 'n', 1, strict: false, keepKeys: false))->toBe([['n' => 1], ['n' => '1']]);
            expect($extension->whereFilter($this->env, false, $array, 'n', 1, strict: true, keepKeys: false))->toBe([['n' => 1]]);
        });

        it('defaults value to true', function () {
            $extension = new ArrayTwigExtension($this->pageLifecycle, $this->env);

            $array = [['enabled' => true], ['enabled' => false]];

            expect($extension->whereFilter($this->env, false, $array, 'enabled', keepKeys: false))->toBe([['enabled' => true]]);
        });

        it('throws when the key contains a disallowed character while sandboxed', function (string $key) {
            $extension = new ArrayTwigExtension($this->pageLifecycle, $this->env);

            $extension->whereFilter($this->env, true, [], $key);
        })->with([
            'dot' => ['foo.bar'],
            'opening bracket' => ['foo[bar'],
            'closing bracket' => ['foo]bar'],
        ])->throws(RuntimeError::class);

        it('allows keys with those characters when not sandboxed', function (string $key) {
            $extension = new ArrayTwigExtension($this->pageLifecycle, $this->env);

            expect($extension->whereFilter($this->env, false, [], $key))->toBe([]);
        })->with([
            'dot' => ['foo.bar'],
            'opening bracket' => ['foo[bar'],
            'closing bracket' => ['foo]bar'],
        ]);
    });

    describe('sandboxed keys', function () {
        beforeEach(function () {
            $inner = new stdClass;
            $inner->secret = 'shh';
            $outer = new stdClass;
            $outer->inner = $inner;
            $this->target = new class
            {
                public int $calls = 0;

                public function boom(): bool
                {
                    $this->calls++;

                    return true;
                }
            };
            $this->variables = ['outer' => $outer, 'inner' => $inner, 'target' => $this->target];
        });

        it('rejects keys that access anything the template couldn’t access directly', function (string $template) {
            try {
                app(TemplateManager::class)->renderSandboxedString($template, $this->variables);
            } finally {
                expect($this->target->calls)->toBe(0);
            }
        })->with([
            'column with a key path' => ["{{ [outer]|column(['inner', 'secret'])|first }}"],
            'column with an index key path' => ["{{ [outer]|column(v => 1, ['inner', 'secret'])|keys|first }}"],
            'multisort with a nested key path' => ["{{ [outer, outer]|multisort([['inner.secret', 'asc']])|length }}"],
            'where with a callable array' => ["{{ ['x']|where([target, 'boom'])|length }}"],
            'contains with a callable array' => ["{{ ['x']|contains([target, 'boom']) ? 'y' : 'n' }}"],
            'multisort with a callable array' => ["{{ ['x', 'y']|multisort([target, 'boom'])|length }}"],
            'where with a disallowed property' => ["{{ [inner]|where('secret', 'shh')|length }}"],
            'contains with a disallowed property' => ["{{ [inner]|contains('secret', 'shh') ? 'y' : 'n' }}"],
            'multisort with a disallowed property' => ["{{ [inner, inner]|multisort('secret')|length }}"],
        ])->throws(TwigError::class);

        it('still allows string keys, integer keys, and arrow functions', function () {
            $result = app(TemplateManager::class)->renderSandboxedString(implode('|', [
                "{{ [{a: 'x'}, {a: 'y'}]|column('a')|join(',') }}",
                "{{ [{a: 2}, {a: 1}]|multisort('a')|column('a')|join(',') }}",
                "{{ [{a: 1}, {a: 2}]|where('a', 2)|length }}",
                '{{ [{a: 1}, {a: 2}]|where(i => i.a == 2)|length }}',
                "{{ [{a: 1}]|contains('a', 1) ? 'y' : 'n' }}",
                "{{ [[1, 2], [3, 4]]|column(1)|join(',') }}",
            ]));

            expect($result)->toBe('x,y|1,2|1|1|y|2,4');
        });
    });
});
