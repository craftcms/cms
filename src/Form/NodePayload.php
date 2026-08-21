<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form;

use CraftCms\Cms\Form\Contracts\Node;
use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\Optional;

readonly class NodePayload implements JsonSerializable
{
    /**
     * @param  class-string<Node>  $type
     * @param  array<string, mixed>  $props
     * @param  list<NodePayload>|null  $children
     */
    public function __construct(
        public string $type,
        public string $component,
        #[LiteralTypeScriptType('Record<string, unknown>')]
        public array $props,
        #[Optional]
        public ?string $uid = null,
        #[Optional]
        public ?ControlPayload $control = null,
        #[Optional]
        public ?array $children = null,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $payload = [
            'type' => $this->type,
            'component' => $this->component,
            'props' => $this->props,
        ];

        if ($this->uid !== null) {
            $payload['uid'] = $this->uid;
        }

        if ($this->control !== null) {
            $payload['control'] = $this->control->jsonSerialize();
        }

        if ($this->children !== null) {
            $payload['children'] = array_map(
                fn (NodePayload $child): array => $child->jsonSerialize(),
                $this->children,
            );
        }

        return $payload;
    }
}
