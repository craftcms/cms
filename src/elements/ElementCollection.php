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
        /** @var array<class-string<TElement>,TElement[]> $elementsByClass */
        $elementsByClass = $this->groupBy(fn(ElementInterface $element) => $element::class)->all();
        $elementsService = Craft::$app->getElements();
        foreach ($elementsByClass as $class => $classElements) {
            $elementsService->eagerLoadElements($class, $this->items, $with);
        }
        return $this;
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
}
