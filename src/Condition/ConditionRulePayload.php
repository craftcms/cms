<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use CraftCms\Cms\Form\FormPayload;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

readonly class ConditionRulePayload
{
    /** @param array<string, mixed> $config */
    public function __construct(
        #[LiteralTypeScriptType('Record<string, unknown>')]
        public array $config,
        public string $label,
        public ?string $hint,
        public bool $showHint,
        public FormPayload $form,
    ) {}
}
