<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui;

use InvalidArgumentException;
use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

/**
 * @since 6.0.0
 */
readonly class UiPayload implements JsonSerializable
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

    /** @param list<string> $scope */
    public function forScope(array $scope): self
    {
        if ($this->scope === $scope) {
            return $this;
        }

        $nested = $this->findNestedUi($this->nodes, $scope);

        if ($nested === null) {
            throw new InvalidArgumentException(sprintf('UI scope [%s] was not found.', implode('.', $scope)));
        }

        return new self(
            scope: $nested->scope,
            refreshable: $nested->refreshable,
            nodes: $nested->nodes,
            values: $this->values,
            errors: array_values(array_filter(
                $this->errors,
                fn (array $error): bool => array_slice($error['path'], 0, count($scope)) === $scope,
            )),
            globalErrors: [],
        );
    }

    /**
     * @param  list<NodePayload>  $nodes
     * @param  list<string>  $scope
     */
    private function findNestedUi(array $nodes, array $scope): ?NestedUiPayload
    {
        foreach ($nodes as $node) {
            foreach ($node->control->uis ?? [] as $ui) {
                if ($ui->scope === $scope) {
                    return $ui;
                }

                $nested = $this->findNestedUi($ui->nodes, $scope);

                if ($nested !== null) {
                    return $nested;
                }
            }

            $nested = $this->findNestedUi($node->children ?? [], $scope);

            if ($nested !== null) {
                return $nested;
            }
        }

        return null;
    }
}
