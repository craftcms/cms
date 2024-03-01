<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use Craft;
use craft\base\ElementInterface;
use Illuminate\Support\Collection;

/**
 * ElementCollection represents a collection of elements.
 *
 * @template TKey of array-key
 * @template TValue of ElementInterface
 * @extends Collection<TKey, TValue>
 *
 * @method TValue one(callable|null $callback, mixed $default)
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 */
class ElementCollection extends Collection
{
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
     * Eager-loads related elements for the collected elements.
     *
     * See [Eager-Loading Elements](https://craftcms.com/docs/4.x/dev/eager-loading-elements.html) for a full explanation of how to work with this parameter.
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
        $first = $this->first();
        if ($first instanceof ElementInterface) {
            Craft::$app->getElements()->eagerLoadElements(get_class($first), $this->items, $with);
        }
        return $this;
    }

    /**
     * Enhances the Illuminate Collection's sort()
     * to respect the locale when sorting with SORT_LOCALE_STRING
     *
     * @param $callback
     * @return ElementCollection
     */
    public function sort($callback = null)
    {
        if (is_int($callback) && $callback === SORT_LOCALE_STRING) {
            setlocale(LC_COLLATE, str_replace('-', '_', Craft::$app->locale->id));
            $results = parent::sort($callback);
            setlocale(
                LC_CTYPE,
                'C.UTF-8', // libc >= 2.13
                'C.utf8' // different spelling
            );

            return $results;
        }

        return parent::sort($callback);
    }

    /**
     * Enhances the Illuminate Collection's sortDesc()
     * to respect the locale when sorting with SORT_LOCALE_STRING
     *
     * @param $options
     * @return ElementCollection
     */
    public function sortDesc($options = SORT_REGULAR): Collection
    {
        if ($options === SORT_LOCALE_STRING) {
            setlocale(LC_COLLATE, str_replace('-', '_', Craft::$app->locale->id));
            $results = parent::sortDesc($options);
            setlocale(
                LC_CTYPE,
                'C.UTF-8', // libc >= 2.13
                'C.utf8' // different spelling
            );

            return $results;
        }

        return parent::sortDesc($options);
    }

    /**
     * Enhances the Illuminate Collection's sortBy() (and sortByDesc())
     * to respect the locale when sorting with SORT_LOCALE_STRING
     *
     * @param $callback
     * @param $options
     * @param $descending
     * @return Collection
     */
    public function sortBy($callback, $options = SORT_REGULAR, $descending = false): Collection
    {
        if ($options === SORT_LOCALE_STRING) {
            setlocale(LC_COLLATE, str_replace('-', '_', Craft::$app->locale->id));
            $results = parent::sortBy($callback, $options, $descending);
            setlocale(
                LC_CTYPE,
                'C.UTF-8', // libc >= 2.13
                'C.utf8' // different spelling
            );

            return $results;
        }

        return parent::sortBy($callback, $options, $descending);
    }

    /**
     * Enhances the Illuminate Collection's sortKeys() (and sortKeysDesc())
     *  to respect the locale when sorting with SORT_LOCALE_STRING
     *
     * @param $options
     * @param $descending
     * @return ElementCollection
     */
    public function sortKeys($options = SORT_REGULAR, $descending = false)
    {
        if ($options === SORT_LOCALE_STRING) {
            setlocale(LC_COLLATE, str_replace('-', '_', Craft::$app->locale->id));
            $results = parent::sortKeys($options, $descending);
            setlocale(
                LC_CTYPE,
                'C.UTF-8', // libc >= 2.13
                'C.utf8' // different spelling
            );

            return $results;
        }

        return parent::sortKeys($options, $descending);
    }
}
