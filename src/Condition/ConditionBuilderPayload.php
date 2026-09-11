<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

readonly class ConditionBuilderPayload
{
    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $value
     * @param  array<string, ConditionRulePayload>  $rules
     * @param  list<array{value: string, label: string, hint: ?string, showHint: bool, group: ?string}>  $ruleTypes
     */
    public function __construct(
        #[LiteralTypeScriptType('Record<string, unknown>')]
        public array $config,
        #[LiteralTypeScriptType('Record<string, unknown>')]
        public array $value,
        public array $rules,
        public array $ruleTypes,
        public string $addRuleLabel,
    ) {}
}
