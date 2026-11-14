<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Extensions;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Support\Arr;
use Illuminate\Support\Collection;
use IteratorAggregate;
use Override;
use Traversable;
use Twig\Environment as TwigEnvironment;
use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Sandbox\SecurityError;
use Twig\TwigFilter;
use Twig\TwigFunction;

use function CraftCms\Cms\renderObjectTemplate;

/**
 * @since 6.0.0
 */
class ArrayTwigExtension extends AbstractExtension
{
    #[Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('column', $this->columnFilter(...), ['needs_environment' => true, 'needs_is_sandboxed' => true]),
            new TwigFilter('contains', $this->containsFilter(...), ['needs_environment' => true, 'needs_is_sandboxed' => true]),
            new TwigFilter('diff', 'array_diff'),
            new TwigFilter('filter', $this->filterFilter(...), ['needs_environment' => true, 'needs_is_sandboxed' => true]),
            new TwigFilter('firstWhere', $this->firstWhereFilter(...), ['needs_environment' => true, 'needs_is_sandboxed' => true]),
            new TwigFilter('flatten', Arr::flatten(...)),
            new TwigFilter('group', $this->groupFilter(...), ['needs_is_sandboxed' => true]),
            new TwigFilter('indexOf', $this->indexOfFilter(...)),
            new TwigFilter('intersect', 'array_intersect'),
            new TwigFilter('map', $this->mapFilter(...), ['needs_environment' => true, 'needs_is_sandboxed' => true]),
            new TwigFilter('merge', $this->mergeFilter(...)),
            new TwigFilter('multisort', $this->multisortFilter(...), ['needs_environment' => true, 'needs_is_sandboxed' => true]),
            new TwigFilter('push', $this->pushFilter(...)),
            new TwigFilter('reduce', $this->reduceFilter(...), ['needs_environment' => true, 'needs_is_sandboxed' => true]),
            new TwigFilter('sort', $this->sortFilter(...), ['needs_environment' => true, 'needs_is_sandboxed' => true]),
            new TwigFilter('unique', 'array_unique'),
            new TwigFilter('unshift', $this->unshiftFilter(...)),
            new TwigFilter('values', 'array_values'),
            new TwigFilter('where', $this->whereFilter(...), ['needs_environment' => true, 'needs_is_sandboxed' => true]),
            new TwigFilter('without', $this->withoutFilter(...)),
            new TwigFilter('withoutKey', $this->withoutKeyFilter(...)),
        ];
    }

    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('collect', $this->collectFunction(...)),
            new TwigFunction('combine', 'array_combine'),
            new TwigFunction('shuffle', $this->shuffleFunction(...)),
        ];
    }

    /**
     * @param  iterable<array-key, mixed>  $array
     * @return array<array-key, mixed>
     *
     * @throws RuntimeError
     */
    public function sortFilter(TwigEnvironment $env, bool $isSandboxed, iterable $array, string|callable|null $arrow = null): array
    {
        CoreExtension::checkArrow($isSandboxed, $arrow, 'sort', 'filter');

        return CoreExtension::sort($env, $isSandboxed, $array, $arrow);
    }

    /** @throws RuntimeError */
    public function reduceFilter(TwigEnvironment $env, bool $isSandboxed, mixed $array, mixed $arrow, mixed $initial = null): mixed
    {
        CoreExtension::checkArrow($isSandboxed, $arrow, 'reduce', 'filter');

        return CoreExtension::reduce($env, $isSandboxed, $array, $arrow, $initial);
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws RuntimeError
     */
    public function mapFilter(TwigEnvironment $env, bool $isSandboxed, mixed $array, mixed $arrow = null): array
    {
        CoreExtension::checkArrow($isSandboxed, $arrow, 'map', 'filter');

        return CoreExtension::map($env, $isSandboxed, $array, $arrow);
    }

    /**
     * @param  iterable<array-key, mixed>  $arr
     * @return array<array-key, mixed>
     *
     * @throws RuntimeError
     */
    public function filterFilter(TwigEnvironment $env, bool $isSandboxed, iterable $arr, ?callable $arrow = null): array
    {
        if ($arrow === null) {
            if ($arr instanceof Traversable) {
                $arr = iterator_to_array($arr);
            }

            return array_filter($arr);
        }

        CoreExtension::checkArrow($isSandboxed, $arrow, 'filter', 'filter');

        $filtered = CoreExtension::filter($env, $isSandboxed, $arr, $arrow);

        if (is_array($filtered)) {
            return $filtered;
        }

        return iterator_to_array($filtered);
    }

    /** @param iterable<array-key, mixed> $array */
    public function firstWhereFilter(TwigEnvironment $env, bool $isSandboxed, iterable $array, callable|string $key, mixed $value = true, bool $strict = false): mixed
    {
        $array = self::checkKeysInSandbox($env, $isSandboxed, $array, [$key], 'firstWhere');

        return collect($array)->firstWhere($key, $strict ? '===' : '==', $value);
    }

    /**
     * @param  iterable<array-key, mixed>  $array
     * @return array<array-key, mixed>
     *
     * @throws SecurityError if an element is an object and `$value` (or `$key`) isn’t an allowed property in a sandboxed environment
     */
    public function columnFilter(TwigEnvironment $env, bool $isSandboxed, iterable $array, mixed $value, mixed $key = null): array
    {
        $array = self::checkKeysInSandbox($env, $isSandboxed, $array, [$value, $key], 'column');

        if ($array instanceof Traversable) {
            $array = iterator_to_array($array);
        }

        return Arr::pluck($array, $value, $key);
    }

    /** @param iterable<array-key, mixed> $array */
    public function containsFilter(TwigEnvironment $env, bool $isSandboxed, iterable $array, callable|string $key, mixed $value = true, bool $strict = false): bool
    {
        $array = self::checkKeysInSandbox($env, $isSandboxed, $array, [$key], 'contains');

        return Arr::contains($array, $key, $value, $strict);
    }

    /**
     * Filters an array to only the values where a given key (the name of a sub-array key or sub-object property)
     * is set to a given value. Array keys are preserved by default.
     *
     * @param  iterable<array-key, mixed>  $array
     * @return array<array-key, mixed>
     */
    public function whereFilter(TwigEnvironment $env, bool $isSandboxed, iterable $array, callable|string $key, mixed $value = true, bool $strict = false, bool $keepKeys = true): array
    {
        $array = self::checkKeysInSandbox($env, $isSandboxed, $array, [$key], 'where');

        $filtered = collect($array)->where($key, $strict ? '===' : '==', $value);

        return $keepKeys ? $filtered->all() : $filtered->values()->all();
    }

    /**
     * Ensures that the keys passed to an array filter in a sandboxed environment can only access what the
     * template could access directly.
     *
     * Each key must be a string or integer that doesn’t contain any `.`, `[`, or `]` characters, or an arrow
     * function (which the sandbox checks on its own). Object elements of `$array` must allow access to each key
     * the same way they would if the template had accessed it directly, e.g. `element.name`. If `$array` is a single
     * non-traversable object, it’s treated as a single element.
     *
     * @param  array<array-key, mixed>  $keys
     * @return ($array is iterable ? iterable<array-key, mixed> : mixed) `$array`, converted to an array if it was an
     * object, so it can be safely iterated (again)
     *
     * @throws RuntimeError if a key isn’t allowed in a sandboxed environment
     * @throws SecurityError if an element is an object and a key isn’t an allowed property/method in a sandboxed environment
     *
     * @internal
     */
    public static function checkKeysInSandbox(TwigEnvironment $env, bool $isSandboxed, mixed $array, array $keys, string $filterName): mixed
    {
        if (! $isSandboxed) {
            return $array;
        }

        $names = [];
        foreach ($keys as $key) {
            if ($key === null || $key instanceof Closure) {
                continue;
            }

            // Arrays would be treated as nested key paths, and other callables would be called directly
            if (! is_string($key) && ! is_int($key)) {
                throw new RuntimeError(sprintf('Keys passed to the "%s" filter must be strings, integers, or arrow functions in sandbox mode.', $filterName));
            }

            $key = (string) $key;
            foreach (['.', '[', ']'] as $char) {
                if (str_contains($key, $char)) {
                    throw new RuntimeError(sprintf('The key name passed to the "%s" filter must not contain any "%s" characters in sandbox mode.', $filterName, $char));
                }
            }

            $names[] = $key;
        }

        if ($array instanceof Traversable) {
            $array = iterator_to_array($array);
        } elseif (is_object($array)) {
            // $array is a single object rather than a list of rows, so iterating over it would mean enumerating
            // its public properties without any sandbox checks. Treat it as a single row instead.
            $array = [$array];
        }

        if (! empty($names) && is_iterable($array)) {
            /** @var SandboxExtension $sandbox */
            $sandbox = $env->getExtension(SandboxExtension::class);
            foreach ($array as $element) {
                if (is_object($element)) {
                    foreach ($names as $name) {
                        $sandbox->checkPropertyAllowed($element, $name);
                    }
                }
            }
        }

        return $array;
    }

    /**
     * @param  iterable<array-key, mixed>  $arr
     * @return array<string, list<mixed>>
     *
     * @throws RuntimeError
     */
    public function groupFilter(bool $isSandboxed, iterable $arr, callable|string $arrow): array
    {
        $groups = [];

        if (is_string($arrow)) {
            // No need to call checkArrow() here since strings are always interpreted as nested fields,
            // which get passed to renderObjectTemplate() as `{name}`
            $template = '{'.$arrow.'}';
            foreach ($arr as $item) {
                $groupKey = renderObjectTemplate($template, $item);
                $groups[$groupKey][] = $item;
            }
        } else {
            CoreExtension::checkArrow($isSandboxed, $arrow, 'group', 'filter');

            foreach ($arr as $key => $item) {
                $groupKey = (string) $arrow($item, $key);
                $groups[$groupKey][] = $item;
            }
        }

        return $groups;
    }

    public function indexOfFilter(mixed $haystack, mixed $needle, ?int $default = -1): ?int
    {
        if (is_string($haystack)) {
            $index = strpos($haystack, (string) $needle);
        } elseif (is_array($haystack)) {
            $index = array_search($needle, $haystack, false);
        } elseif (is_object($haystack) && $haystack instanceof IteratorAggregate) {
            $index = false;

            foreach ($haystack as $i => $item) {
                if ($item == $needle) {
                    $index = $i;
                    break;
                }
            }
        }

        if (isset($index) && $index !== false) {
            return $index;
        }

        return $default;
    }

    /**
     * @param  iterable<array-key, mixed>  $arr1
     * @param  iterable<array-key, mixed>  $arr2
     * @return array<array-key, mixed>
     */
    public function mergeFilter(iterable $arr1, iterable $arr2, bool $recursive = false): array
    {
        if ($arr1 instanceof Traversable) {
            $arr1 = iterator_to_array($arr1);
        }

        if ($arr2 instanceof Traversable) {
            $arr2 = iterator_to_array($arr2);
        }

        if ($recursive) {
            return Arr::merge($arr1, $arr2);
        }

        return CoreExtension::merge($arr1, $arr2);
    }

    /**
     * @param  int|array<array-key, int>  $direction
     * @param  int|array<array-key, int>  $sortFlag
     * @return array<array-key, mixed>
     */
    public function multisortFilter(TwigEnvironment $env, bool $isSandboxed, mixed $array, mixed $key, int|array $direction = SORT_ASC, int|array $sortFlag = SORT_REGULAR): array
    {
        $array = self::checkKeysInSandbox($env, $isSandboxed, $array, is_array($key) ? $key : [$key], 'multisort');

        $array = array_merge($array);

        return collect($array)
            ->sortBy($key, collect($sortFlag)->sum(), $direction === SORT_DESC)
            ->all();
    }

    /**
     * @param  array<array-key, mixed>  $array
     * @return array<array-key, mixed>
     */
    public function pushFilter(array $array, mixed ...$values): array
    {
        array_push($array, ...$values);

        return $array;
    }

    /**
     * @param  array<array-key, mixed>  $array
     * @return array<array-key, mixed>
     */
    public function unshiftFilter(array $array, mixed ...$values): array
    {
        array_unshift($array, ...$values);

        return $array;
    }

    /** @return array<array-key, mixed> */
    public function withoutFilter(mixed $arr, mixed $exclude, bool $strict = false): array
    {
        $items = is_null($arr) || is_scalar($arr) || $arr instanceof \UnitEnum
            ? Arr::wrap($arr)
            : Arr::from($arr);

        return Collection::make($items)
            ->reject(fn ($value) => in_array($value, Arr::wrap($exclude), $strict))
            ->all();
    }

    /**
     * @param  array<array-key, string>|string  $key
     * @return array<array-key, mixed>
     */
    public function withoutKeyFilter(mixed $arr, array|string $key): array
    {
        $arr = (array) $arr;

        if (! is_array($key)) {
            $key = [$key];
        }

        foreach ($key as $k) {
            Arr::forget($arr, $k);
        }

        return $arr;
    }

    /** @return Collection<array-key, mixed> */
    public function collectFunction(mixed $var): Collection
    {
        $items = is_null($var) || is_scalar($var) || $var instanceof \UnitEnum
            ? Arr::wrap($var)
            : Arr::from($var);
        $collection = Collection::make($items);

        if ($collection->isNotEmpty() && $collection->doesntContain(fn ($item) => ! $item instanceof ElementInterface)) {
            return ElementCollection::make($collection);
        }

        return $collection;
    }

    /**
     * @param  iterable<array-key, mixed>  $arr
     * @return list<mixed>
     */
    public function shuffleFunction(iterable $arr): array
    {
        if ($arr instanceof Traversable) {
            $arr = iterator_to_array($arr, false);
        } else {
            $arr = array_merge($arr);
        }

        shuffle($arr);

        return $arr;
    }
}
