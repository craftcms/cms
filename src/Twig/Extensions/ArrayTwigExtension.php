<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Extensions;

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
use Twig\TwigFilter;
use Twig\TwigFunction;

use function CraftCms\Cms\renderObjectTemplate;

class ArrayTwigExtension extends AbstractExtension
{
    #[Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('column', $this->columnFilter(...), ['needs_is_sandboxed' => true]),
            new TwigFilter('contains', $this->containsFilter(...), ['needs_is_sandboxed' => true]),
            new TwigFilter('diff', 'array_diff'),
            new TwigFilter('filter', $this->filterFilter(...), ['needs_environment' => true, 'needs_is_sandboxed' => true]),
            new TwigFilter('firstWhere', $this->firstWhereFilter(...), ['needs_is_sandboxed' => true]),
            new TwigFilter('flatten', Arr::flatten(...)),
            new TwigFilter('group', $this->groupFilter(...), ['needs_is_sandboxed' => true]),
            new TwigFilter('indexOf', $this->indexOfFilter(...)),
            new TwigFilter('intersect', 'array_intersect'),
            new TwigFilter('map', $this->mapFilter(...), ['needs_environment' => true, 'needs_is_sandboxed' => true]),
            new TwigFilter('merge', $this->mergeFilter(...)),
            new TwigFilter('multisort', $this->multisortFilter(...), ['needs_is_sandboxed' => true]),
            new TwigFilter('push', $this->pushFilter(...)),
            new TwigFilter('reduce', $this->reduceFilter(...), ['needs_environment' => true, 'needs_is_sandboxed' => true]),
            new TwigFilter('sort', $this->sortFilter(...), ['needs_environment' => true, 'needs_is_sandboxed' => true]),
            new TwigFilter('unique', 'array_unique'),
            new TwigFilter('unshift', $this->unshiftFilter(...)),
            new TwigFilter('values', 'array_values'),
            new TwigFilter('where', $this->whereFilter(...), ['needs_is_sandboxed' => true]),
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
    public function firstWhereFilter(bool $isSandboxed, iterable $array, callable|string $key, mixed $value = true, bool $strict = false): mixed
    {
        $this->preventDottedNameInSandbox($isSandboxed, $key, 'firstWhere');

        return collect($array)->firstWhere($key, $strict ? '===' : '==', $value);
    }

    /**
     * @param  iterable<array-key, mixed>  $array
     * @return array<array-key, mixed>
     */
    public function columnFilter(bool $isSandboxed, iterable $array, mixed $value, mixed $key = null): array
    {
        $this->preventDottedNameInSandbox($isSandboxed, $value, 'column');
        $this->preventDottedNameInSandbox($isSandboxed, $key, 'column');

        return Arr::pluck($array, $value, $key);
    }

    /** @param iterable<array-key, mixed> $array */
    public function containsFilter(bool $isSandboxed, iterable $array, callable|string $key, mixed $value = true, bool $strict = false): bool
    {
        $this->preventDottedNameInSandbox($isSandboxed, $key, 'contains');

        return Arr::contains($array, $key, $value, $strict);
    }

    /**
     * Filters an array to only the values where a given key (the name of a sub-array key or sub-object property)
     * is set to a given value. Array keys are preserved by default.
     *
     * @param  iterable<array-key, mixed>  $array
     * @return array<array-key, mixed>
     */
    public function whereFilter(bool $isSandboxed, iterable $array, callable|string $key, mixed $value = true, bool $strict = false, bool $keepKeys = true): array
    {
        $this->preventDottedNameInSandbox($isSandboxed, $key, 'where');

        $filtered = collect($array)->where($key, $strict ? '===' : '==', $value);

        return $keepKeys ? $filtered->all() : $filtered->values()->all();
    }

    /**
     * Throws a RuntimeError if the given name/key is a string containing a "." character and the environment is
     * sandboxed.
     *
     * @throws RuntimeError
     */
    private function preventDottedNameInSandbox(bool $isSandboxed, mixed $name, string $filterName): void
    {
        if (! $isSandboxed || ! is_string($name)) {
            return;
        }

        foreach (['.', '[', ']'] as $char) {
            if (str_contains($name, $char)) {
                throw new RuntimeError(sprintf('The key name passed to the "%s" filter must not contain any "%s" characters in sandbox mode.', $filterName, $char));
            }
        }
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
    public function multisortFilter(bool $isSandboxed, mixed $array, mixed $key, int|array $direction = SORT_ASC, int|array $sortFlag = SORT_REGULAR): array
    {
        foreach (is_array($key) ? $key : [$key] as $k) {
            $this->preventDottedNameInSandbox($isSandboxed, $k, 'multisort');
        }

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
