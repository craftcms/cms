<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Exceptions;

use CraftCms\Cms\Support\Arr;
use Illuminate\Database\RecordsNotFoundException;

/**
 * @template TElement of \craft\base\ElementInterface
 */
final class ElementNotFoundException extends RecordsNotFoundException
{
    /**
     * Name of the affected element.
     *
     * @var class-string<TElement>
     */
    private string $element;

    /**
     * The affected element IDs.
     *
     * @var array<int, int|string>
     */
    private array $ids;

    /**
     * Set the affected Eloquent model and instance ids.
     *
     * @param  class-string<TElement>  $element
     * @param  array<int, int|string>|int|string  $ids
     * @return $this
     */
    public function setElement(string $element, array|int|string $ids = []): self
    {
        $this->element = $element;
        $this->ids = Arr::wrap($ids);

        $this->message = "No query results for element [{$element}]";

        if (count($this->ids) > 0) {
            $this->message .= ' '.implode(', ', $this->ids);
        } else {
            $this->message .= '.';
        }

        return $this;
    }

    /**
     * Get the affected element.
     *
     * @return class-string<TElement>
     */
    public function getModel(): string
    {
        return $this->element;
    }

    /**
     * Get the affected element IDs.
     *
     * @return array<int, int|string>
     */
    public function getIds(): array
    {
        return $this->ids;
    }
}
