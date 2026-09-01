<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form;

use InvalidArgumentException;
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

    /** @param list<string> $scope */
    public function forScope(array $scope): self
    {
        if ($this->scope === $scope) {
            return $this;
        }

        $nested = $this->findNestedForm($this->nodes, $scope);

        if ($nested === null) {
            throw new InvalidArgumentException(sprintf('Form scope [%s] was not found.', implode('.', $scope)));
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
    private function findNestedForm(array $nodes, array $scope): ?NestedFormPayload
    {
        foreach ($nodes as $node) {
            foreach ($node->control->forms ?? [] as $form) {
                if ($form->scope === $scope) {
                    return $form;
                }

                $nested = $this->findNestedForm($form->nodes, $scope);

                if ($nested !== null) {
                    return $nested;
                }
            }

            $nested = $this->findNestedForm($node->children ?? [], $scope);

            if ($nested !== null) {
                return $nested;
            }
        }

        return null;
    }
}
