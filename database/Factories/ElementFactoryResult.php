<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Field\Models\Field;
use Illuminate\Support\Collection;

/**
 * @template TElement of Element
 */
final readonly class ElementFactoryResult
{
    /**
     * @param  Collection<string, Field>  $fields
     */
    public function __construct(
        public Element $element,
        public Collection $fields,
    ) {}

    public function field(string $handle): ?Field
    {
        return $this->fields->get($handle);
    }
}
