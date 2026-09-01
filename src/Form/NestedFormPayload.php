<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form;

use JsonSerializable;

readonly class NestedFormPayload implements JsonSerializable
{
    /**
     * @param  list<string>  $scope
     * @param  list<NodePayload>  $nodes
     */
    public function __construct(
        public array $scope,
        public bool $refreshable,
        public array $nodes,
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
        ];
    }
}
