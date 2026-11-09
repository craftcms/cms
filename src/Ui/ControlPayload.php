<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui;

use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Enums\ControlMode;
use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

/**
 * @since 6.0.0
 */
readonly class ControlPayload implements JsonSerializable
{
    /**
     * @param  class-string<Control>  $type
     * @param  array<string, mixed>  $props
     * @param  list<string>  $path
     * @param  list<string>  $deltaGroup
     * @param  list<NestedUiPayload>  $uis
     * @param  list<string>  $valueProps
     */
    public function __construct(
        public string $type,
        public string $component,
        #[LiteralTypeScriptType('Record<string, unknown>')]
        public array $props,
        public array $path,
        public ControlMode $mode,
        public array $deltaGroup,
        public array $uis = [],
        public bool $reactive = false,
        public mixed $emptyValue = null,
        public bool $nestsUis = false,
        public bool $omitNullValue = false,
        public bool $readable = false,
        public bool $writable = false,
        public array $valueProps = [],
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
        ] + ($this->reactive ? ['reactive' => true] : [])
            + ($this->emptyValue === null ? [] : ['emptyValue' => $this->emptyValue])
            + ($this->nestsUis ? ['nestsUis' => true] : [])
            + ($this->omitNullValue ? ['omitNullValue' => true] : [])
            + ($this->readable ? ['readable' => true] : [])
            + ($this->writable ? ['writable' => true] : [])
            + ($this->valueProps === [] ? [] : ['valueProps' => $this->valueProps])
            + ($this->uis === [] ? [] : ['uis' => array_map(
                fn (NestedUiPayload $ui): array => $ui->jsonSerialize(),
                $this->uis,
            )]);
    }
}
