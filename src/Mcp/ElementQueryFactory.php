<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Str;
use Mcp\Exception\ToolCallException;

/**
 * @since 6.0.0
 */
readonly class ElementQueryFactory
{
    public function __construct(private Elements $elements) {}

    public function make(string $type): ElementQueryInterface
    {
        return $this->elements->createElementQuery($this->resolve($type));
    }

    /** @return class-string<ElementInterface> */
    public function resolve(string $type): string
    {
        if (in_array($type, $this->elements->getAllElementTypes(), true)) {
            return $type;
        }

        $elementType = $this->elements->getElementTypeByRefHandle($type)
            ?? $this->elements->getElementTypeByRefHandle(Str::singular($type));

        if ($elementType === null || ! in_array($elementType, $this->elements->getAllElementTypes(), true)) {
            throw new ToolCallException("Unsupported element type [$type].");
        }

        return $elementType;
    }

    /** @return list<class-string<ElementInterface>> */
    public function registeredTypes(): array
    {
        return $this->elements->getAllElementTypes();
    }
}
