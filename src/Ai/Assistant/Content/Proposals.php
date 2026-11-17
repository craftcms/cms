<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant\Content;

use Closure;
use CraftCms\Cms\Ai\Assistant\Data\Proposal;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Elements\ContentBlock as ContentBlockElement;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\FieldLayout\FieldPreviews;
use CraftCms\Cms\Mcp\Capabilities\Elements;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\NodePayload;
use Illuminate\Support\Facades\DB;
use Mcp\Exception\ToolCallException;

/**
 * Turns the changes the AI assistant proposes into changes the user can apply to their editor.
 *
 * The changes are set on a copy of the element, as the MCP server’s `elements_validate` tool sets them, and the
 * element’s form is compiled before and after. Each field whose value changed is a proposal: text as a diff, and
 * anything else as the field’s own read-only control, before and after. Nothing is saved.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class Proposals
{
    public function __construct(
        private Elements $elements,
        private FieldPreviews $previews,
    ) {}

    /**
     * @param  list<ElementContext>  $contexts
     * @param  int  $id  The ID of an element in the context, or nested in one.
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $fields
     * @return array{proposals: list<Proposal>, notInEditor: list<string>} The changes, and the attributes and fields
     *                                                                     that aren’t in the element’s editor, which the user couldn’t apply.
     *
     * @throws ToolCallException if the element can’t be changed, or the values aren’t valid.
     */
    public function propose(array $contexts, int $id, array $attributes, array $fields): array
    {
        foreach ($contexts as $context) {
            $found = $context->editable ? $this->find($context->element, $id) : null;

            if ($found !== null) {
                // Setting a Matrix field’s value on a draft can create drafts of its entries, which mustn’t be kept.
                return $this->rolledBack(fn (): array => $this->changes($context, $found[0], $found[1], $attributes, $fields));
            }
        }

        throw new ToolCallException("Element $id isn’t in the context, or the user can’t change it.");
    }

    /**
     * @param  list<string>  $path  The path of the element’s fields in its editor’s form.
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $fields
     * @return array{proposals: list<Proposal>, notInEditor: list<string>}
     */
    private function changes(ElementContext $context, ElementInterface $element, array $path, array $attributes, array $fields): array
    {
        $copy = clone $element;
        $this->elements->populate($element::refHandle(), $copy, $attributes, $fields);

        $namespace = [...$context->namespace, ...$path];
        $current = $this->previews->compile($element, $element, $namespace);
        $proposed = $this->previews->compile($element, $copy, $namespace);

        if (! $current || ! $proposed) {
            return ['proposals' => [], 'notInEditor' => [...array_keys($attributes), ...array_keys($fields)]];
        }

        // What the editor has controls for, by their paths below the element’s own.
        $controls = array_map(
            static fn (NodePayload $node): array => array_slice($node->control->path ?? [], count($namespace)),
            $this->previews->fieldNodes($current->nodes),
        );
        $notInEditor = [
            ...array_filter(array_keys($attributes), static fn (string $attribute): bool => ! array_any($controls, static fn (array $control): bool => ($control[0] ?? null) === $attribute)),
            ...array_filter(array_keys($fields), static fn (string $handle): bool => ! array_any($controls, static fn (array $control): bool => $control[0] === 'fields' && ($control[1] ?? null) === $handle)),
        ];

        // Unique to these proposals, so their previews’ fields don’t share IDs with others in the conversation.
        $previewNamespace = 'aiAssistant'.Str::random(8);
        $previews = null;
        $proposals = [];

        foreach ($this->previews->fieldNodes($proposed->nodes) as $node) {
            $controlPath = $node->control->path ?? [];
            $before = data_get($current->values, $controlPath);
            $after = data_get($proposed->values, $controlPath);

            if ($before === $after) {
                continue;
            }

            $layoutUid = (string) ($node->props['layoutUid'] ?? $node->uid);
            $diff = FieldPreviews::diff($before, $after, [$element, $copy], $element->getFieldLayout()?->getElementByUid($layoutUid));
            $isText = $diff !== null;
            $preview = null;

            if (! $isText) {
                $previews ??= [
                    $this->previews->compile($element, $element, "{$previewNamespace}Current", ControlMode::ReadOnly),
                    $this->previews->compile($element, $copy, "{$previewNamespace}Proposed", ControlMode::ReadOnly),
                ];
                $currentNode = $previews[0] ? $this->previews->findNode($previews[0]->nodes, $layoutUid) : null;
                $proposedNode = $previews[1] ? $this->previews->findNode($previews[1]->nodes, $layoutUid) : null;

                if (! $previews[0] || ! $previews[1] || ! $currentNode || ! $proposedNode) {
                    continue;
                }

                $preview = [
                    'current' => $this->previews->payloadFor($previews[0], $currentNode),
                    'proposed' => $this->previews->payloadFor($previews[1], $proposedNode),
                ];
            }

            $label = is_string($node->props['label'] ?? null) ? $node->props['label'] : implode('.', $controlPath);

            $proposals[] = new Proposal(
                key: "{$context->id}:".implode('.', $controlPath),
                ref: $context->ref,
                label: $path === [] ? $label : "{$element->getUiLabel()} › $label",
                element: $context->element->getUiLabel(),
                elementId: $context->element->id,
                canonicalId: $context->element->getCanonicalId(),
                siteId: $context->element->siteId,
                cpEditUrl: $context->element->getCpEditUrl(),
                path: $controlPath,
                value: $after,
                diff: $diff,
                preview: $preview,
                node: $isText ? null : $node,
            );
        }

        return ['proposals' => $proposals, 'notInEditor' => array_values($notInEditor)];
    }

    /**
     * Finds an element, or an element nested in its fields as the editor shows them, with the path of its fields in
     * the editor’s form.
     *
     * @param  list<string>  $path
     * @return array{ElementInterface, list<string>}|null
     */
    private function find(ElementInterface $element, int $id, array $path = []): ?array
    {
        if ($element->id === $id || $element->getCanonicalId() === $id) {
            return [$element, $path];
        }

        foreach ($element->getFieldLayout()?->getVisibleCustomFieldElements($element) ?? [] as $layoutField) {
            $field = $layoutField->getField();
            $nested = match (true) {
                $field instanceof Matrix => $field->inlineEntries($element) ?? [],
                $field instanceof ContentBlock => array_filter([$element->getFieldValue($field->handle)], static fn (mixed $block): bool => $block instanceof ContentBlockElement),
                default => [],
            };

            foreach ($nested as $identity => $child) {
                $childPath = [...$path, 'fields', $field->handle, ...($field instanceof Matrix ? ['entries', (string) $identity] : [])];

                if ($found = $this->find($child, $id, $childPath)) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    private function rolledBack(Closure $callback): mixed
    {
        DB::beginTransaction();

        try {
            return $callback();
        } finally {
            DB::rollBack();
        }
    }
}
