<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Extensions;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Template;
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
use Twig\Source;
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
            new TwigFilter('group', $this->groupFilter(...), ['needs_environment' => true, 'needs_is_sandboxed' => true]),
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
            new TwigFilter('without', $this->withoutFilter(...), ['needs_is_sandboxed' => true]),
            new TwigFilter('withoutKey', $this->withoutKeyFilter(...), ['needs_is_sandboxed' => true]),
        ];
    }

    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('collect', $this->collectFunction(...), ['needs_is_sandboxed' => true]),
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
        $array = self::sandboxItems($isSandboxed, $array);

        if ($isSandboxed && ! $key instanceof Closure) {
            // Lazy, so rows after the first match aren't read
            $match = collect($array)
                ->lazy()
                ->map(self::checkLooseComparison($env, true, self::sandboxKey($env, true, $key, 'firstWhere'), $value, $strict))
                ->map(fn (mixed $value) => ['value' => $value])
                ->where('value', $strict ? '===' : '==', $value)
                ->keys()
                ->first();

            return $match === null ? null : $array[$match];
        }

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
        $array = self::sandboxItems($isSandboxed, $array);
        $value = self::sandboxKey($env, $isSandboxed, $value, 'column');
        $key = self::sandboxKey($env, $isSandboxed, $key, 'column');

        if ($isSandboxed && $key instanceof Closure) {
            // Arr::pluck() casts objects to strings to use them as keys
            $key = self::checkToString($env, $key);
        }

        if ($array instanceof Traversable) {
            $array = iterator_to_array($array);
        }

        return Arr::pluck($array, $value, $key);
    }

    /** @param iterable<array-key, mixed> $array */
    public function containsFilter(TwigEnvironment $env, bool $isSandboxed, iterable $array, callable|string $key, mixed $value = true, bool $strict = false): bool
    {
        $array = self::sandboxItems($isSandboxed, $array);

        if ($isSandboxed && ! $key instanceof Closure) {
            // Lazy, so rows after the first match aren't read
            return collect($array)
                ->lazy()
                ->map(self::checkLooseComparison($env, true, self::sandboxKey($env, true, $key, 'contains'), $value, $strict))
                ->map(fn (mixed $value) => ['value' => $value])
                ->contains('value', $strict ? '===' : '==', $value);
        }

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
        $array = self::sandboxItems($isSandboxed, $array);

        if ($isSandboxed && ! $key instanceof Closure) {
            $matches = collect($array)
                ->map(self::checkLooseComparison($env, true, self::sandboxKey($env, true, $key, 'where'), $value, $strict))
                ->map(fn (mixed $value) => ['value' => $value])
                ->where('value', $strict ? '===' : '==', $value);
            $filtered = collect($array)->intersectByKeys($matches);
        } else {
            $filtered = collect($array)->where($key, $strict ? '===' : '==', $value);
        }

        return $keepKeys ? $filtered->all() : $filtered->values()->all();
    }

    /**
     * Returns the items a filter or function should work with in a sandboxed environment.
     *
     * Converting an object to an array, or iterating over a non-traversable object, would expose all of its
     * properties without any sandbox checks, including protected and private ones. So in a sandboxed environment,
     * traversable objects are converted with their own iterator, and other objects are treated as a single item.
     *
     * @return ($value is iterable<array-key, mixed> ? iterable<array-key, mixed> : mixed)
     *
     * @internal
     */
    public static function sandboxItems(bool $isSandboxed, mixed $value): mixed
    {
        if (! $isSandboxed || ! is_object($value)) {
            return $value;
        }

        return $value instanceof Traversable ? iterator_to_array($value) : [$value];
    }

    /**
     * Returns the key a filter should read from each item in a sandboxed environment.
     *
     * Rather than letting array helpers read the key themselves, string and integer keys are replaced with an arrow
     * function that reads them with Twig’s attribute lookup, so each read gets the same sandbox checks it would get
     * if the template had accessed it directly, e.g. `item.key`. Arrow functions and `null` are returned as-is, and
     * anything else (such as key paths or callables) is rejected.
     *
     * @return ($key is null ? null : ($isSandboxed is true ? Closure(mixed): mixed : mixed))
     *
     * @throws RuntimeError if the key isn’t allowed in a sandboxed environment
     *
     * @internal
     */
    public static function sandboxKey(TwigEnvironment $env, bool $isSandboxed, mixed $key, string $filterName): mixed
    {
        if (! $isSandboxed || $key === null || $key instanceof Closure) {
            return $key;
        }

        if (! is_string($key) && ! is_int($key)) {
            throw new RuntimeError(sprintf('Keys passed to the "%s" filter must be strings, integers, or arrow functions in sandbox mode.', $filterName));
        }

        foreach (['.', '[', ']'] as $char) {
            if (str_contains((string) $key, $char)) {
                throw new RuntimeError(sprintf('The key name passed to the "%s" filter must not contain any "%s" characters in sandbox mode.', $filterName, $char));
            }
        }

        $source = new Source('', "$filterName filter");

        return fn (mixed $item) => Template::attribute($env, $source, $item, $key, ignoreStrictCheck: true, sandboxed: true);
    }

    /**
     * Returns the key reader a filter should use when comparing what it reads with a value using `==`.
     *
     * PHP calls `__toString()` on an object when comparing it with a string, and on objects nested in an array when
     * comparing the array with another array. Twig’s own `==` operator requires those objects to be allowed to be cast
     * to strings in a sandboxed environment, so the values read for those comparisons must be too.
     *
     * @param  mixed  $key  The key reader returned by sandboxKey()
     * @param  mixed  $value  The value that each read value will be compared with
     * @param  bool  $strict  Whether the values will be compared using `===` instead
     * @return ($key is Closure ? Closure(mixed...): mixed : mixed)
     *
     * @internal
     */
    public static function checkLooseComparison(TwigEnvironment $env, bool $isSandboxed, mixed $key, mixed $value, bool $strict): mixed
    {
        if (! $isSandboxed || $strict || ! $key instanceof Closure || (! is_string($value) && ! is_array($value))) {
            return $key;
        }

        $sandbox = $env->getExtension(SandboxExtension::class);

        return function (mixed ...$args) use ($key, $value, $sandbox): mixed {
            $read = $key(...$args);

            if (is_string($value) ? is_object($read) : is_array($read)) {
                $sandbox->ensureToStringAllowed($read);
            }

            return $read;
        };
    }

    /**
     * @param  iterable<array-key, mixed>  $arr
     * @return array<string, list<mixed>>
     *
     * @throws RuntimeError
     */
    public function groupFilter(TwigEnvironment $env, bool $isSandboxed, iterable $arr, callable|string $arrow): array
    {
        $groups = [];

        if (is_string($arrow) && $isSandboxed) {
            // Read the property like other array filters’ keys, rather than rendering it as an object template, which
            // would use the site template mode’s Twig environment, even when a sandboxed control panel template is rendering
            $arrow = self::sandboxKey($env, true, $arrow, 'group');
        }

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
                $groupKey = $arrow($item, $key);

                if ($isSandboxed && is_object($groupKey)) {
                    $env->getExtension(SandboxExtension::class)->ensureToStringAllowed($groupKey);
                }

                $groups[(string) $groupKey][] = $item;
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
        $array = self::sandboxItems($isSandboxed, $array);
        $array = array_merge($array);

        if ($isSandboxed && is_array($key)) {
            // Collection::sortBy() would read each key itself, and call callable arrays like `['SomeClass', 'someMethod']`,
            // so read each key up front and sort by the values that were read instead
            $readers = [];
            $comparisons = [];

            foreach (array_values($key) as $i => $comparison) {
                $comparison = Arr::wrap($comparison);
                $prop = $comparison[0] ?? null;

                if ($prop instanceof Closure) {
                    $comparison[0] = fn (array $a, array $b) => $prop($a['item'], $b['item']);
                } else {
                    $readers[$i] = self::checkToString($env, self::sandboxKey($env, true, $prop, 'multisort') ?? fn (mixed $item) => $item);
                    $comparison[0] = "values.$i";
                }

                $comparisons[] = $comparison;
            }

            return collect($array)
                ->map(fn (mixed $item) => ['item' => $item, 'values' => array_map(fn (Closure $read) => $read($item), $readers)])
                ->sortBy($comparisons, collect($sortFlag)->sum())
                ->map(fn (array $row) => $row['item'])
                ->all();
        }

        $key = self::sandboxKey($env, $isSandboxed, $key, 'multisort');

        if ($isSandboxed && $key instanceof Closure) {
            $key = self::checkToString($env, $key);
        }

        return collect($array)
            ->sortBy($key, collect($sortFlag)->sum(), $direction === SORT_DESC)
            ->all();
    }

    /**
     * Wraps a key reader whose results may be cast to strings, such as for sorting or as array keys, so objects it
     * returns must be allowed to be cast to strings in the sandbox.
     */
    private static function checkToString(TwigEnvironment $env, Closure $read): Closure
    {
        $sandbox = $env->getExtension(SandboxExtension::class);

        return function (mixed ...$args) use ($read, $sandbox): mixed {
            $value = $read(...$args);

            // Comparisons also call __toString() on objects nested in arrays
            return $sandbox->ensureToStringAllowed($value);
        };
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
    public function withoutFilter(bool $isSandboxed, mixed $arr, mixed $exclude, bool $strict = false): array
    {
        $arr = self::sandboxItems($isSandboxed, $arr);
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
    public function withoutKeyFilter(bool $isSandboxed, mixed $arr, array|string $key): array
    {
        $arr = (array) self::sandboxItems($isSandboxed, $arr);

        if (! is_array($key)) {
            $key = [$key];
        }

        foreach ($key as $k) {
            Arr::forget($arr, $k);
        }

        return $arr;
    }

    /** @return Collection<array-key, mixed> */
    public function collectFunction(bool $isSandboxed, mixed $var): Collection
    {
        $var = self::sandboxItems($isSandboxed, $var);
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
