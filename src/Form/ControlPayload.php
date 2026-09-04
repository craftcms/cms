<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form;

use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Enums\ControlMode;
use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

readonly class ControlPayload implements JsonSerializable
{
    /**
     * @param  class-string<Control>  $type
     * @param  array<string, mixed>  $props
     * @param  list<string>  $path
     * @param  list<string>  $deltaGroup
     * @param  list<NestedFormPayload>  $forms
     */
    public function __construct(
        public string $type,
        public string $component,
        #[LiteralTypeScriptType('Record<string, unknown>')]
        public array $props,
        public array $path,
        public ControlMode $mode,
        public array $deltaGroup,
        public array $forms = [],
        public bool $reactive = false,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'component' => $this->component,
            'props' => $this->props,
            'path' => $this->path,
            'mode' => $this->mode->value,
            'deltaGroup' => $this->deltaGroup,
        ] + ($this->reactive ? ['reactive' => true] : []) + ($this->forms === [] ? [] : ['forms' => array_map(
            fn (NestedFormPayload $form): array => $form->jsonSerialize(),
            $this->forms,
        )]);
    }
}
