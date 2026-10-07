<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Mcp\Public\ElementType;
use Mcp\Exception\ToolCallException;

/**
 * @since 6.0.0
 */
readonly class PublicElementTypes
{
    public function __construct(private ElementQueryFactory $elementQueries) {}

    /** @return array<string, class-string<ElementInterface>> */
    public function all(): array
    {
        $types = [];

        foreach ($this->elementQueries->registeredTypes() as $type) {
            $types[$this->name($type)] = $type;
        }

        return $types;
    }

    /** @return class-string<ElementInterface>|null */
    public function resolve(string $name): ?string
    {
        return $this->all()[$name] ?? null;
    }

    public function make(string $name): ElementType
    {
        $type = $this->find($name);

        if ($type === null) {
            throw new ToolCallException("Unsupported public element type [$name].");
        }

        return $type;
    }

    public function find(string $name): ?ElementType
    {
        $class = $this->resolve($name);

        return $class === null ? null : new ElementType($this->name($class), $class);
    }

    /** @param class-string<ElementInterface> $type */
    public function name(string $type): string
    {
        $handle = $type::refHandle();

        return is_string($handle) && $handle !== '' ? $handle : $type;
    }
}
