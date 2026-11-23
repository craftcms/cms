<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use CraftCms\Cms\Twig\Extensions\ArrayTwigExtension;
use CraftCms\Cms\Twig\Twig;
use CraftCms\Cms\View\PageLifecycle;
use CraftCms\Cms\View\TemplateManager;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Collection;
use Twig\Error\Error as TwigError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;

class ArrayTwigExtensionTestStaticTarget
{
    public static int $calls = 0;

    public static function boom(): int
    {
        self::$calls++;

        return 0;
    }
}

class ArrayTwigExtensionTestStringableHolder
{
    #[AllowedInSandbox]
    public object $value;

    public function __construct()
    {
        $this->value = new class
        {
            public function __toString(): string
            {
                return 'shh';
            }
        };
    }
}

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
        expect($extension->withoutKeyFilter(false, ['a' => 1, 'b' => 2], 'a'))->toBe(['b' => 2]);
        expect($extension->collectFunction(false, [1, 2, 3]))->toBeInstanceOf(Collection::class);
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

            expect($extension->groupFilter($this->env, false, $array, 'status'))->toBe([
                'live' => [['status' => 'live'], ['status' => 'live']],
                'draft' => [['status' => 'draft']],
            ]);
        });

        it('groups using a closure arrow when sandboxed', function () {
            $extension = new ArrayTwigExtension($this->pageLifecycle, $this->env);

            $array = [1, 2, 3, 4];

            expect($extension->groupFilter($this->env, true, $array, fn (int $v) => $v % 2 === 0 ? 'even' : 'odd'))->toBe([
                'odd' => [1, 3],
                'even' => [2, 4],
            ]);
        });

        it('throws when the arrow is a non-Closure callable while sandboxed', function () {
            $extension = new ArrayTwigExtension($this->pageLifecycle, $this->env);

            // a first-class callable (`...`) is itself a Closure, so use an array callable to get a genuinely non-Closure callable
            $extension->groupFilter($this->env, true, [1, 2], [$extension, 'indexOfFilter']);
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
            // ArrayAccess objects that aren’t one of Twig’s array-like classes get the same checks as properties
            $arrayAccess = new class implements ArrayAccess
            {
                public function offsetExists(mixed $offset): bool
                {
                    return $offset === 'secret';
                }

                public function offsetGet(mixed $offset): mixed
                {
                    return 'shh';
                }

                public function offsetSet(mixed $offset, mixed $value): void {}

                public function offsetUnset(mixed $offset): void {}
            };
            $stringable = new class
            {
                public function __toString(): string
                {
                    return 'shh';
                }
            };
            $this->variables = ['outer' => $outer, 'inner' => $inner, 'target' => $this->target, 'arrayAccess' => $arrayAccess, 'stringable' => $stringable, 'holder' => new ArrayTwigExtensionTestStringableHolder];
            ArrayTwigExtensionTestStaticTarget::$calls = 0;
        });

        it('rejects keys that access anything the template couldn’t access directly', function (string $template) {
            try {
                app(TemplateManager::class)->renderSandboxedString($template, $this->variables);
            } finally {
                expect($this->target->calls)->toBe(0);
                expect(ArrayTwigExtensionTestStaticTarget::$calls)->toBe(0);
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
            'column with a disallowed ArrayAccess offset' => ["{{ [arrayAccess]|column('secret')|first }}"],
            'group with an arrow function returning an object that can’t be a string' => ['{{ [1]|group(i => stringable)|keys|first }}'],
            'column with an index that can’t be a string' => ['{{ [1]|column(v => 1, v => stringable)|keys|first }}'],
            'multisort with a sort key that can’t be a string' => ['{{ [1, 2]|multisort(v => stringable, 4, 2)|length }}'],
            'multisort with an array sort key containing an object that can’t be a string' => ['{{ [1, 2]|multisort(v => [stringable])|length }}'],
            // Comparing objects with strings using == calls __toString()
            'where comparing an object that can’t be a string with a string' => ["{{ [holder]|where('value', 'shh')|length }}"],
            'contains comparing an object that can’t be a string with a string' => ["{{ [holder]|contains('value', 'shh') ? 'y' : 'n' }}"],
        ])->throws(TwigError::class);

        // group used to render property names as object templates in the site template mode’s environment
        it('keeps group in the sandbox in control panel templates', function (string $template) {
            app(TemplateManager::class)->renderSandboxedString($template, $this->variables, TemplateMode::Cp);
        })->with([
            'disallowed property' => ["{{ [inner]|group('secret')|keys|first }}"],
            'Twig tag' => ["{{ [inner]|group('x}{{ object.secret }}{x')|keys|first }}"],
        ])->throws(TwigError::class);

        it('still allows strict comparisons with objects that can’t be strings', function () {
            expect(app(TemplateManager::class)->renderSandboxedString("{{ [holder]|where('value', 'shh', true)|length }}", $this->variables))->toBe('0');
        });

        it('stops reading rows after firstWhere and contains find a match', function () {
            // Reading the disallowed property on the row after the match would throw
            $rows = [['enabled' => true], $this->variables['inner']];

            expect(app(TemplateManager::class)->renderSandboxedString("{{ rows|contains('enabled', true) ? 'y' : 'n' }}", ['rows' => $rows]))->toBe('y');

            // firstWhere isn’t in the default sandbox allowlist, so call it directly with the sandbox enabled
            $twig = app(Twig::class)->get(TemplateMode::Site);
            $checker = $twig->getExtension(SandboxExtension::class)->getChecker();
            $checker->setSandboxed(true);

            try {
                expect($twig->getExtension(ArrayTwigExtension::class)->firstWhereFilter($twig, true, $rows, 'enabled'))->toBe(['enabled' => true]);
            } finally {
                $checker->setSandboxed(false);
            }
        });

        it('never calls callable arrays passed to multisort', function () {
            // Collection::sortBy() would call this, but in the sandbox each item is read as a key name instead
            app(TemplateManager::class)->renderSandboxedString(
                "{{ ['x', 'y']|multisort(['ArrayTwigExtensionTestStaticTarget', 'boom'])|length }}",
            );

            expect(ArrayTwigExtensionTestStaticTarget::$calls)->toBe(0);
        });

        it('still allows string keys, integer keys, and arrow functions', function () {
            $result = app(TemplateManager::class)->renderSandboxedString(implode('|', [
                "{{ [{a: 'x'}, {a: 'y'}]|column('a')|join(',') }}",
                "{{ [{a: 2}, {a: 1}]|multisort('a')|column('a')|join(',') }}",
                "{{ [{a: 1}, {a: 2}]|where('a', 2)|length }}",
                '{{ [{a: 1}, {a: 2}]|where(i => i.a == 2)|length }}',
                "{{ [{a: 1}]|contains('a', 1) ? 'y' : 'n' }}",
                "{{ [[1, 2], [3, 4]]|column(1)|join(',') }}",
                "{{ [{a: 'x'}, {a: 'y'}, {a: 'x'}]|group('a')|keys|join(',') }}",
            ]));

            expect($result)->toBe('x,y|1,2|1|1|y|2,4|x,y');
            expect(app(TemplateManager::class)->renderSandboxedString("{{ [{a: 'x'}]|group('a')|keys|first }}", templateMode: TemplateMode::Cp))->toBe('x');
        });

        it('still allows offset reads on Twig’s array-like classes', function () {
            $result = app(TemplateManager::class)->renderSandboxedString(implode('|', [
                "{{ rows|column(0)|join(',') }}",
                '{{ rows|where(0, 8)|length }}',
                "{{ rows|multisort(0)|column(0)|join(',') }}",
            ]), ['rows' => [new ArrayObject([8]), new ArrayObject([7])]]);

            expect($result)->toBe('8,7|1|7,8');
        });
    });
});
