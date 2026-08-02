<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Forms\Data;

use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

#[LiteralTypeScriptType("{ name: string; operator: 'equals' | 'notEquals' | 'lessThan' | 'lessThanOrEqual' | 'greaterThan' | 'greaterThanOrEqual' | 'beginsWith' | 'endsWith' | 'contains' | 'in' | 'notIn'; value: JsonValue } | { name: string; operator: 'empty' | 'notEmpty' } | { all: VisibilityConditionData[] } | { any: VisibilityConditionData[] }")]
readonly class VisibilityConditionData implements JsonSerializable
{
    /** @param array<string, mixed> $condition */
    public function __construct(
        public array $condition,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return array_map(
            fn (mixed $value): mixed => is_array($value)
                ? array_map(
                    fn (mixed $condition): mixed => $condition instanceof self
                        ? $condition->jsonSerialize()
                        : $condition,
                    $value,
                )
                : $value,
            $this->condition,
        );
    }

    public function withInputNamePrefix(string $prefix): self
    {
        $condition = $this->condition;

        if (isset($condition['name'])) {
            $condition['name'] = "{$prefix}.{$condition['name']}";
        }

        foreach (['all', 'any'] as $group) {
            if (isset($condition[$group])) {
                $condition[$group] = array_map(
                    fn (self $child): self => $child->withInputNamePrefix($prefix),
                    $condition[$group],
                );
            }
        }

        return new self($condition);
    }
}
