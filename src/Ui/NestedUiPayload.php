<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui;

use JsonSerializable;

/**
 * @since 6.0.0
 */
readonly class NestedUiPayload implements JsonSerializable
{
    /**
     * @param  list<string>  $scope
     * @param  list<NodePayload>  $nodes
     * @param  bool  $reused  Whether this is a placeholder for a nested UI the request didn't change. It has no nodes,
     *                        and the payload has no values under its scope. The client keeps the ones it shows.
     */
    public function __construct(
        public array $scope,
        public bool $refreshable,
        public array $nodes,
        public bool $reused = false,
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
            ...$this->reused ? ['reused' => true] : [],
        ];
    }
}
