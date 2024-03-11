<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use Illuminate\Support\Collection;
use Twig\Markup;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;

/**
 * ElementCollection represents a collection of elements.
 *
 * @template TValue of ElementInterface
 * @extends Collection<array-key, TValue>
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
     * @return Collection<array-key, int>
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
}
