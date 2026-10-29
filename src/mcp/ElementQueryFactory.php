<?php

declare(strict_types=1);

namespace craft\mcp;

use craft\base\ElementInterface;
use craft\elements\Category;
use craft\elements\db\ElementQueryInterface;
use craft\elements\GlobalSet;
use craft\elements\Tag;
use craft\services\Elements;
use Mcp\Exception\ToolCallException;
use yii\helpers\Inflector;

/**
 * @since 5.12.0
 */
readonly class ElementQueryFactory
{
    /**
     * Deprecated element types that MCP doesn't expose.
     */
    private const EXCLUDED_TYPES = [Category::class, GlobalSet::class, Tag::class];

    public function __construct(private Elements $elements)
    {
    }

    public function make(string $type): ElementQueryInterface
    {
        return $this->elements->createElementQuery($this->resolve($type));
    }

    /** @return class-string<ElementInterface> */
    public function resolve(string $type): string
    {
        if (in_array($type, $this->registeredTypes(), true)) {
            return $type;
        }

        $elementType = $this->elements->getElementTypeByRefHandle($type)
            ?? $this->elements->getElementTypeByRefHandle(Inflector::singularize($type));

        if ($elementType === null || !in_array($elementType, $this->registeredTypes(), true)) {
            throw new ToolCallException("Unsupported element type [$type].");
        }

        return $elementType;
    }

    /** @return list<class-string<ElementInterface>> */
    public function registeredTypes(): array
    {
        return array_values(array_diff($this->elements->getAllElementTypes(), self::EXCLUDED_TYPES));
    }
}
