<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Extensions;

use craft\base\ElementInterface;
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
            new TwigFilter('column', Arr::pluck(...)),
            new TwigFilter('contains', Arr::contains(...)),
            new TwigFilter('diff', 'array_diff'),
            new TwigFilter('filter', $this->filterFilter(...), ['needs_environment' => true]),
            new TwigFilter('flatten', Arr::flatten(...)),
            new TwigFilter('group', $this->groupFilter(...)),
            new TwigFilter('indexOf', $this->indexOfFilter(...)),
            new TwigFilter('intersect', 'array_intersect'),
            new TwigFilter('map', $this->mapFilter(...), ['needs_environment' => true]),
            new TwigFilter('merge', $this->mergeFilter(...)),
            new TwigFilter('multisort', $this->multisortFilter(...)),
            new TwigFilter('push', $this->pushFilter(...)),
            new TwigFilter('reduce', $this->reduceFilter(...), ['needs_environment' => true]),
            new TwigFilter('sort', $this->sortFilter(...), ['needs_environment' => true]),
            new TwigFilter('unique', 'array_unique'),
            new TwigFilter('unshift', $this->unshiftFilter(...)),
            new TwigFilter('values', 'array_values'),
            new TwigFilter('where', Arr::where(...)),
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
     * @throws RuntimeError
     */
    public function sortFilter(TwigEnvironment $env, iterable $array, string|callable|null $arrow = null): array
    {
        CoreExtension::checkArrow($env, $arrow, 'sort', 'filter');

        return CoreExtension::sort($env, $array, $arrow);
    }

    /**
     * @throws RuntimeError
     */
    public function reduceFilter(TwigEnvironment $env, mixed $array, mixed $arrow, mixed $initial = null): mixed
    {
        CoreExtension::checkArrow($env, $arrow, 'reduce', 'filter');

        return CoreExtension::reduce($env, $array, $arrow, $initial);
    }

    /**
     * @throws RuntimeError
     */
    public function mapFilter(TwigEnvironment $env, mixed $array, mixed $arrow = null): array
    {
        CoreExtension::checkArrow($env, $arrow, 'map', 'filter');

        return CoreExtension::map($env, $array, $arrow);
    }

    /**
     * @throws RuntimeError
     */
    public function filterFilter(TwigEnvironment $env, iterable $arr, ?callable $arrow = null): array
    {
        /** @var array|Traversable $arr */
        if ($arrow === null) {
            if ($arr instanceof Traversable) {
                $arr = iterator_to_array($arr);
            }

            return array_filter($arr);
        }

        CoreExtension::checkArrow($env, $arrow, 'filter', 'filter');

        $filtered = CoreExtension::filter($env, $arr, $arrow);

        if (is_array($filtered)) {
            return $filtered;
        }

        return iterator_to_array($filtered);
    }

    /**
     * @throws RuntimeError
     */
    public function groupFilter(iterable $arr, callable|string $arrow): array
    {
        $groups = [];

        if (is_string($arrow)) {
            $template = '{'.$arrow.'}';
            foreach ($arr as $item) {
                $groupKey = renderObjectTemplate($template, $item);
                $groups[$groupKey][] = $item;
            }
        } else {
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

    public function multisortFilter(mixed $array, mixed $key, int|array $direction = SORT_ASC, int|array $sortFlag = SORT_REGULAR): array
    {
        $array = array_merge($array);

        return collect($array)
            ->sortBy($key, $sortFlag, $direction === SORT_DESC)
            ->all();
    }

    public function pushFilter(array $array, mixed ...$values): array
    {
        array_push($array, ...$values);

        return $array;
    }

    public function unshiftFilter(array $array, mixed ...$values): array
    {
        array_unshift($array, ...$values);

        return $array;
    }

    public function withoutFilter(mixed $arr, mixed $exclude, bool $strict = false): array
    {
        return Collection::make($arr)
            ->reject(fn ($value) => in_array($value, Arr::wrap($exclude), $strict))
            ->all();
    }

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

    public function collectFunction(mixed $var): Collection
    {
        $collection = Collection::make($var);

        if ($collection->isNotEmpty() && $collection->doesntContain(fn ($item) => ! $item instanceof ElementInterface)) {
            return ElementCollection::make($collection);
        }

        return $collection;
    }

    public function shuffleFunction(iterable $arr): array
    {
        /** @var array|Traversable $arr */
        if ($arr instanceof Traversable) {
            $arr = iterator_to_array($arr, false);
        } else {
            $arr = array_merge($arr);
        }

        shuffle($arr);

        return $arr;
    }
}
