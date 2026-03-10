<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Twig\Markup;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;

/**
 * ElementCollection represents a collection of elements.
 *
 * @template TKey of array-key
 * @template TElement of ElementInterface
 * @extends Collection<TKey,TElement>
 *
 * @method TElement one(callable|null $callback, mixed $default)
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
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
     * @param int|TElement|Arrayable<array-key,int>|iterable<array-key,int> $key
     * @param TFindDefault $default
     * @return static<TKey,TElement>|TElement|TFindDefault
     * @since 5.2.0
     */
    public function find(mixed $key, mixed $default = null): mixed
    {
        if ($key instanceof ElementInterface) {
            return Arr::first(
                $this->items,
                fn(ElementInterface $element) => $element->siteSettingsId === $key->siteSettingsId,
                $default,
            );
        }

        if ($key instanceof Arrayable) {
            $key = $key->toArray();
        }

        if (is_array($key)) {
            if ($this->isEmpty()) {
                /** @phpstan-ignore-next-line */
                return new static();
            }

            return $this->whereIn('id', $key);
        }

        return Arr::first($this->items, fn(ElementInterface $element) => $element->id === $key, $default);
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
     * @param array|string $with The property value
     * @return $this
     */
    public function with(array|string $with): static
    {
        /** @var array<class-string<TElement>,TElement[]> $elementsByClass */
        $elementsByClass = $this->groupBy(fn(ElementInterface $element) => $element::class)->all();
        $elementsService = Craft::$app->getElements();
        foreach ($elementsByClass as $class => $classElements) {
            $elementsService->eagerLoadElements($class, $this->items, $with);
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
     * @param (callable(TElement,TKey):bool)|TElement|string|int $key
     * @param mixed $operator
     * @param mixed $value
     * @return bool
     */
    public function contains($key, $operator = null, $value = null)
    {
        if (func_num_args() > 1 || $this->useAsCallable($key)) {
            return parent::contains(...func_get_args());
        }

        if ($key instanceof ElementInterface) {
            return parent::contains(fn(ElementInterface $element) => $element->siteSettingsId === $key->siteSettingsId);
        }

        if (is_int($key)) {
            return parent::contains(fn(ElementInterface $element) => $element->id === $key);
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
        return Collection::make(array_map(fn(ElementInterface $element): int => $element->id, $this->items));
    }

    /**
     * Merge the collection with the given elements.
     *
     * Any elements with a matching ID and site ID will be replaced.
     *
     * @param iterable<array-key,TElement> $items
     * @return static
     */
    public function merge($items)
    {
        $elements = $this->keyBy('siteSettingsId')->all();

        foreach ($items as $element) {
            $elements[$element->siteSettingsId] = $element;
        }

        /** @phpstan-ignore-next-line */
        return new static(array_values($elements));
    }

    /**
     * Runs a map over each of the items.
     *
     * @template TMapValue
     *
     * @param callable(TElement,TKey):TMapValue $callback
     * @return Collection<TKey,TMapValue>|static<TKey,TMapValue>
     */
    public function map(callable $callback)
    {
        $result = parent::map($callback);
        /** @phpstan-ignore instanceof.alwaysTrue */
        return $result->contains(fn($item) => !$item instanceof ElementInterface) ? $result->toBase() : $result;
    }

    /**
     * Runs an associative map over each of the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @template TMapWithKeysKey of array-key
     * @template TMapWithKeysValue
     *
     * @param callable(TElement,TKey):array<TMapWithKeysKey,TMapWithKeysValue> $callback
     * @return Collection<TMapWithKeysKey,TMapWithKeysValue>|static<TMapWithKeysKey,TMapWithKeysValue>
     */
    public function mapWithKeys(callable $callback)
    {
        $result = parent::mapWithKeys($callback);
        /** @phpstan-ignore instanceof.alwaysTrue */
        return $result->contains(fn($item) => !$item instanceof ElementInterface) ? $result->toBase() : $result;
    }

    /**
     * Reloads fresh element instances from the database for all the elements.
     *
     * @return static
     * @since 5.2.0
     */
    public function fresh(): static
    {
        if ($this->isEmpty()) {
            /** @phpstan-ignore-next-line */
            return new static();
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

        /** @phpstan-ignore-next-line */
        return $this
            ->filter(fn(ElementInterface $element) => isset($freshElements[$element::class][$element->siteSettingsId]))
            ->map(fn(ElementInterface $element) => $freshElements[$element::class][$element->siteSettingsId]);
    }

    /**
     * Returns a new collection with the elements that are not present in the given array.
     */
    public function diff($items)
    {
        /** @phpstan-ignore-next-line */
        $diff = new static();
        $ids = array_flip(array_map(fn(ElementInterface $element) => $element->siteSettingsId, $items));

        foreach ($this->items as $element) {
            /** @var TElement $element */
            if (!isset($ids[$element->siteSettingsId])) {
                $diff->add($element);
            }
        }

        return $diff;
    }

    /**
     * Returns a new collection with all the elements present in this collection and the provided array.
     *
     * @param  array<array-key,TElement> $items
     * @return static
     */
    public function intersect($items)
    {
        /** @phpstan-ignore-next-line */
        $intersect = new static();

        if (empty($items)) {
            return $intersect;
        }

        $ids = array_flip(array_map(fn(ElementInterface $element) => $element->siteSettingsId, $items));

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
     * @param (callable(TElement,TKey):mixed)|string|null $key
     * @param bool $strict
     * @return static
     */
    public function unique($key = null, $strict = false)
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
     * @param Enumerable<array-key,TKey>|array<array-key,TKey>|string|int|null $keys
     * @return static
     */
    public function only($keys)
    {
        if ($keys === null) {
            /** @phpstan-ignore-next-line */
            return new static($this->items);
        }

        if ($keys instanceof Enumerable) {
            $keys = $keys->toArray();
        } elseif (is_scalar($keys)) {
            $keys = [$keys];
        }

        if (!ArrayHelper::isNumeric($keys)) {
            return parent::only($keys);
        }

        $keys = array_flip($keys);
        $elements = array_filter($this->items, fn(ElementInterface $element) => isset($keys[$element->id]));
        /** @phpstan-ignore-next-line */
        return new static(array_values($elements));
    }

    /**
     * Returns a new collection with all elements except those with the specified keys.
     *
     * If `$keys` is an integer or array of integers, a collection of elements without the same IDs will be returned.
     *
     * @param Enumerable<array-key,TKey>|array<array-key,TKey>|string|int|null $keys
     * @return static
     */
    public function except($keys)
    {
        if ($keys === null) {
            /** @phpstan-ignore-next-line */
            return new static($this->items);
        }

        if ($keys instanceof Enumerable) {
            $keys = $keys->toArray();
        } elseif (is_scalar($keys)) {
            $keys = [$keys];
        }

        if (!ArrayHelper::isNumeric($keys)) {
            return parent::except($keys);
        }

        $keys = array_flip($keys);
        $elements = array_filter($this->items, fn(ElementInterface $element) => !isset($keys[$element->id]));
        /** @phpstan-ignore-next-line */
        return new static(array_values($elements));
    }

    /**
     * Renders the elements using their partial templates.
     *
     * If no partial template exists for an element, its string representation will be output instead.
     *
     * @param array $variables
     * @return Markup
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @see ElementHelper::renderElements()
     * @since 5.0.0
     */
    public function render(array $variables = []): Markup
    {
        return ElementHelper::renderElements($this->items, $variables);
    }

    // The following methods are intercepted to always return base collections.
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     * @return Collection
     */
    public function countBy($countBy = null)
    {
        return $this->toBase()->countBy($countBy);
    }

    /**
     * @inheritdoc
     * @return Collection
     */
    public function collapse()
    {
        return $this->toBase()->collapse();
    }

    /**
     * @inheritdoc
     * @param int|float $depth
     * @return Collection
     */
    public function flatten($depth = INF)
    {
        return $this->toBase()->flatten($depth);
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException
     */
    public function flip()
    {
        throw new NotSupportedException('Not possible to flip element collections.');
    }

    /**
     * @inheritdoc
     * @return Collection
     */
    public function keys()
    {
        return $this->toBase()->keys();
    }

    /**
     * @inheritdoc
     * @return Collection
     */
    public function pad($size, $value)
    {
        return $this->toBase()->pad($size, $value);
    }

    /**
     * @inheritdoc
     * @return Collection
     */
    public function pluck($value, $key = null)
    {
        return $this->toBase()->pluck($value, $key);
    }

    /**
     * @inheritdoc
     * @return Collection
     */
    public function zip($items)
    {
        return $this->toBase()->zip(...func_get_args());
    }
}
