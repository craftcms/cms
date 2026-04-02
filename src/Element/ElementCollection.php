<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use Closure;
use Craft;
use craft\base\ElementInterface;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Elements;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Override;
use RuntimeException;
use Twig\Markup;

/**
 * ElementCollection represents a collection of elements.
 *
 * @template TKey of array-key
 * @template TElement of ElementInterface
 *
 * @extends Collection<TKey,TElement>
 *
 * @method TElement one(callable|null $callback, mixed $default)
 */
class ElementCollection extends Collection
{
    /**
     * Finds an element in the collection.
     *
     * If `$key` is an element instance, the first element with the same ID and site ID.
     *
     * If `$key` is an integer, the first element with the same ID will be returned.
     *
     * @template TFindDefault
     *
     * @param  int|TElement|Arrayable<array-key,int>|iterable<array-key,int>  $key
     * @param  TFindDefault  $default
     * @return static<TKey,TElement>|TElement|TFindDefault
     */
    public function find(mixed $key, mixed $default = null): mixed
    {
        if ($key instanceof ElementInterface) {
            return Arr::first(
                $this->items,
                fn (ElementInterface $element) => $element->siteSettingsId === $key->siteSettingsId,
                $default,
            );
        }

        if ($key instanceof Arrayable) {
            $key = $key->toArray();
        }

        if (is_array($key)) {
            if ($this->isEmpty()) {
                return self::make();
            }

            return $this->whereIn('id', $key);
        }

        return Arr::first($this->items, fn (ElementInterface $element) => $element->id === $key, $default);
    }

    /**
     * Eager-loads related elements for the collected elements.
     *
     * See [Eager-Loading Elements](https://craftcms.com/docs/5.x/development/eager-loading.html) for a full explanation of how to work with this parameter.
     *
     * ---
     *
     * ```twig
     * {# Fetch entries and eager-load the "Related" field’s relations onto them #}
     * {% set entries = craft.entries()
     *   .collect()
     *   .with(['related']) %}
     * ```
     *
     * ```php
     * // Fetch entries and eager-load the "Related" field’s relations onto them
     * $entries = Entry::find()
     *     ->collect()
     *     ->with(['related']);
     * ```
     *
     * @param  array|string  $with  The property value
     */
    public function with(array|string $with): self
    {
        /** @var array<class-string<TElement>,TElement[]> $elementsByClass */
        $elementsByClass = $this->groupBy(fn (ElementInterface $element) => $element::class)->all();

        foreach ($elementsByClass as $class => $classElements) {
            app(Elements::class)->eagerLoadElements($class, $this->items, $with);
        }

        return $this;
    }

    /**
     * Returns whether an element exists within the collection.
     *
     * If `$key` is an element instance, `true` will be returned if the collection contains an element with the same ID
     * and site ID.
     *
     * If `$key` is an integer, `true` will be returned in the collection contains an element with that ID.
     *
     * @param  (callable(TElement,TKey):bool)|TElement|string|int  $key
     */
    #[Override]
    public function contains(mixed $key, mixed $operator = null, mixed $value = null): bool
    {
        if (func_num_args() > 1 || $this->useAsCallable($key)) {
            return parent::contains(...func_get_args());
        }

        if ($key instanceof ElementInterface) {
            return parent::contains(fn (ElementInterface $element) => $element->siteSettingsId === $key->siteSettingsId);
        }

        if (is_int($key)) {
            return parent::contains(fn (ElementInterface $element) => $element->id === $key);
        }

        return false;
    }

    /**
     * Returns a collection of the elements’ IDs.
     *
     * @return Collection<TKey,int>
     */
    public function ids(): Collection
    {
        return $this->pluck('id');
    }

    /**
     * Merge the collection with the given elements.
     *
     * Any elements with a matching ID and site ID will be replaced.
     *
     * @param  iterable<array-key,TElement>  $items
     * @return self<TKey, TElement>
     */
    #[Override]
    public function merge($items): self
    {
        $elements = $this->keyBy('siteSettingsId')->all();

        foreach ($items as $element) {
            $elements[$element->siteSettingsId] = $element;
        }

        return self::make(array_values($elements));
    }

    /**
     * Runs a map over each of the items.
     *
     * @template TMapValue
     *
     * @param  callable(TElement,TKey):TMapValue  $callback
     * @return Collection<TKey,TMapValue>|self<TKey,TMapValue&ElementInterface>
     */
    #[Override]
    public function map(callable $callback): Collection|self
    {
        /** @var Collection<TKey,TMapValue> $result */
        $result = $this->toBase()->map($callback);
        $elements = [];

        foreach ($result as $key => $item) {
            if (! $item instanceof ElementInterface) {
                return $result;
            }

            $elements[$key] = $item;
        }

        return self::makeElementCollection($elements);
    }

    /**
     * Runs an associative map over each of the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @template TMapWithKeysKey of array-key
     * @template TMapWithKeysValue
     *
     * @param  callable(TElement,TKey):array<TMapWithKeysKey,TMapWithKeysValue>  $callback
     * @return Collection<TMapWithKeysKey,TMapWithKeysValue>|self<TMapWithKeysKey,TMapWithKeysValue&ElementInterface>
     */
    #[Override]
    public function mapWithKeys(callable $callback): self|Collection
    {
        /** @var Collection<TMapWithKeysKey,TMapWithKeysValue> $result */
        $result = $this->toBase()->mapWithKeys($callback);
        $elements = [];

        foreach ($result as $key => $item) {
            if (! $item instanceof ElementInterface) {
                return $result;
            }

            $elements[$key] = $item;
        }

        return self::makeElementCollection($elements);
    }

    /**
     * Reloads fresh element instances from the database for all the elements.
     */
    public function fresh(): Collection
    {
        if ($this->isEmpty()) {
            return self::make();
        }

        // Get all the elements' site settings IDs, grouped by element type
        /** @var array<class-string<TElement>,int[]> $idsByClass */
        $idsByClass = [];
        foreach ($this->items as $element) {
            /** @var TElement $element */
            $idsByClass[$element::class][] = $element->siteSettingsId;
        }

        /** @var array<class-string<TElement>,array<int,TElement>> $idsByClass */
        $freshElements = [];

        foreach ($idsByClass as $class => $ids) {
            /** @var class-string<TElement>|class-string<ElementInterface> $class */
            $freshElements[$class] = $class::find()
                ->site('*')
                ->siteSettingsId($ids)
                ->drafts(null)
                ->provisionalDrafts(null)
                ->revisions(null)
                ->status(null)
                ->indexBy('siteSettingsId')
                ->all();
        }

        return $this
            ->filter(fn (ElementInterface $element) => isset($freshElements[$element::class][$element->siteSettingsId]))
            ->map(fn (ElementInterface $element) => $freshElements[$element::class][$element->siteSettingsId]);
    }

    /**
     * Returns a new collection with the elements that are not present in the given array.
     */
    #[Override]
    public function diff($items): self
    {
        $diff = self::make();
        $ids = array_flip(array_map(fn (ElementInterface $element) => $element->siteSettingsId, $items));

        foreach ($this->items as $element) {
            /** @var TElement $element */
            if (! isset($ids[$element->siteSettingsId])) {
                $diff->add($element);
            }
        }

        return $diff;
    }

    /**
     * Returns a new collection with all the elements present in this collection and the provided array.
     *
     * @param  array<array-key,TElement>  $items
     */
    #[Override]
    public function intersect($items): self
    {
        $intersect = self::make();

        if (empty($items)) {
            return $intersect;
        }

        $ids = array_flip(array_map(fn (ElementInterface $element) => $element->siteSettingsId, $items));

        foreach ($this->items as $element) {
            /** @var TElement $element */
            if (isset($ids[$element->siteSettingsId])) {
                $intersect->add($element);
            }
        }

        return $intersect;
    }

    /**
     * Return only unique items from the collection.
     *
     * @param  (callable(TElement,TKey):mixed)|string|null  $key
     * @param  bool  $strict
     */
    #[Override]
    public function unique($key = null, $strict = false): self
    {
        if ($key !== null) {
            return parent::unique($key, $strict);
        }

        return $this->keyBy('id')->values();
    }

    /**
     * Returns a new collection with only the elements with the specified keys.
     *
     * If `$keys` is an integer or array of integers, a collection of elements with the same IDs will be returned.
     *
     * @param  Enumerable<array-key,TKey>|array<array-key,TKey>|string|int|null  $keys
     */
    #[Override]
    public function only($keys): self
    {
        if ($keys === null) {
            return self::make($this->items);
        }

        if ($keys instanceof Enumerable) {
            $keys = $keys->all();
        } elseif (is_scalar($keys)) {
            $keys = [$keys];
        }

        if (! Arr::isNumeric($keys)) {
            return parent::only($keys);
        }

        $keys = array_flip($keys);
        $elements = array_filter($this->items, fn (ElementInterface $element) => isset($keys[$element->id]));

        return self::make(array_values($elements));
    }

    /**
     * Returns a new collection with all elements except those with the specified keys.
     *
     * If `$keys` is an integer or array of integers, a collection of elements without the same IDs will be returned.
     *
     * @param  Enumerable<array-key,TKey>|array<array-key,TKey>|string|int|null  $keys
     */
    #[Override]
    public function except($keys): self
    {
        if ($keys === null) {
            return self::make($this->items);
        }

        if ($keys instanceof Enumerable) {
            $keys = $keys->all();
        } elseif (is_scalar($keys)) {
            $keys = [$keys];
        }

        if (! Arr::isNumeric($keys)) {
            return parent::except($keys);
        }

        $keys = array_flip($keys);
        $elements = array_filter($this->items, fn (ElementInterface $element) => ! isset($keys[$element->id]));

        return self::make(array_values($elements));
    }

    /**
     * Renders the elements using their partial templates.
     *
     * If no partial template exists for an element, its string representation will be output instead.
     *
     * @see ElementHelper::renderElements()
     */
    public function render(array $variables = []): Markup
    {
        return ElementHelper::renderElements($this->items, $variables);
    }

    // The following methods are intercepted to always return base collections.
    // -------------------------------------------------------------------------

    /**
     * @param  (callable(TElement,TKey):(array-key|\UnitEnum))|string|null  $countBy
     * @return Collection<array-key,int>
     */
    #[Override]
    public function countBy($countBy = null): Collection
    {
        return $this->toBase()->countBy($countBy);
    }

    /**
     * @return Collection<int,mixed>
     */
    #[Override]
    public function collapse(): Collection
    {
        return $this->toBase()->collapse();
    }

    /**
     * @param  int|float  $depth
     * @return Collection<int,mixed>
     */
    #[Override]
    public function flatten($depth = INF): Collection
    {
        return $this->toBase()->flatten($depth);
    }

    /**
     * @throws RuntimeException
     */
    #[Override]
    public function flip(): never
    {
        throw new RuntimeException('Not possible to flip element collections.');
    }

    /**
     * @return Collection<int,TKey>
     */
    #[Override]
    public function keys(): Collection
    {
        return $this->toBase()->keys();
    }

    /**
     * @template TPadValue
     *
     * @param  int  $size
     * @param  TPadValue  $value
     * @return Collection<int,TElement|TPadValue>
     */
    #[Override]
    public function pad($size, $value): Collection
    {
        return $this->toBase()->pad($size, $value);
    }

    /**
     * Get an array with the values of a given key.
     *
     * @template TPluckValueReturn
     * @template TPluckKeyReturn of array-key
     *
     * @param  string|array<array-key, string>|Closure(TElement):TPluckValueReturn|null  $value
     * @param  string|Closure(TElement):TPluckKeyReturn|null  $key
     * @return ($value is Closure ? ($key is Closure ? Collection<TPluckKeyReturn, TPluckValueReturn> : Collection<array-key, TPluckValueReturn>) : ($key is Closure ? Collection<TPluckKeyReturn, mixed> : Collection<array-key, mixed>))
     */
    #[Override]
    public function pluck($value, $key = null): Collection
    {
        return $this->toBase()->pluck($value, $key);
    }

    /**
     * @template TZipValue
     *
     * @param  Arrayable<array-key,TZipValue>|iterable<array-key,TZipValue>  ...$items
     * @return Collection<int,Collection<int,mixed>>
     */
    #[Override]
    public function zip($items): Collection
    {
        return $this->toBase()->zip(...func_get_args());
    }

    /**
     * @template TCollectionKey of array-key
     * @template TCollectionElement of ElementInterface
     *
     * @param  array<TCollectionKey,TCollectionElement>  $collection
     * @return self<TCollectionKey,TCollectionElement>
     */
    private static function makeElementCollection(array $collection): self
    {
        return new self($collection);
    }
}
