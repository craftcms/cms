<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form;

use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

readonly class FormPayload implements JsonSerializable
{
    /**
     * @param  list<string>  $scope
     * @param  list<NodePayload>  $nodes
     * @param  array<string, mixed>  $values
     * @param  list<array{path: list<string>, messages: list<string>}>  $errors
     * @param  list<string>  $globalErrors
     */
    public function __construct(
        public array $scope,
        public bool $refreshable,
        public array $nodes,
        #[LiteralTypeScriptType('Record<string, unknown>')]
        public array $values,
        public array $errors,
        public array $globalErrors,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'scope' => $this->scope,
            'refreshable' => $this->refreshable,
            'nodes' => array_map(
                fn (NodePayload $node): array => $node->jsonSerialize(),
                $this->nodes,
            ),
            'values' => $this->values,
            'errors' => $this->errors,
            'globalErrors' => $this->globalErrors,
        ];
    }
}
