<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant\Content;

use Closure;
use CraftCms\Cms\Ai\Assistant\Data\Proposal;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Elements\ContentBlock as ContentBlockElement;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\Mcp\Capabilities\Elements;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\Nodes\ActionMenu;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use Illuminate\Support\Facades\DB;
use Mcp\Exception\ToolCallException;

/**
 * Turns the changes the AI assistant proposes into changes the user can apply to their editor.
 *
 * The changes are set on a copy of the element, as the MCP server’s `elements.validate` tool sets them, and the
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
        private FieldLayoutCompiler $compiler,
    ) {}

    /**
     * @param  list<ElementContext>  $contexts
     * @param  int  $id  The ID of an element in the context, or nested in one.
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $fields
     * @return list<Proposal>
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
     * @return list<Proposal>
     */
    private function changes(ElementContext $context, ElementInterface $element, array $path, array $attributes, array $fields): array
    {
        $copy = clone $element;
        $this->elements->populate($element::refHandle(), $copy, $attributes, $fields);

        $namespace = [...$context->namespace, ...$path];
        $current = $this->compile($element, $element, $namespace);
        $proposed = $this->compile($element, $copy, $namespace);

        if (! $current || ! $proposed) {
            return [];
        }

        // Unique to these proposals, so their previews’ fields don’t share IDs with others in the conversation.
        $previewNamespace = 'aiAssistant'.Str::random(8);
        $previews = null;
        $proposals = [];

        foreach ($this->fieldNodes($proposed->nodes) as $node) {
            $controlPath = $node->control->path ?? [];
            $before = data_get($current->values, $controlPath);
            $after = data_get($proposed->values, $controlPath);

            if ($before == $after) {
                continue;
            }

            $isText = self::text($before) !== null && self::text($after) !== null;
            $preview = null;

            if (! $isText) {
                $previews ??= [
                    $this->compile($element, $element, "{$previewNamespace}Current", ControlMode::ReadOnly),
                    $this->compile($element, $copy, "{$previewNamespace}Proposed", ControlMode::ReadOnly),
                ];
                $layoutUid = (string) ($node->props['layoutUid'] ?? $node->uid);
                $currentNode = $previews[0] ? $this->findNode($previews[0]->nodes, $layoutUid) : null;
                $proposedNode = $previews[1] ? $this->findNode($previews[1]->nodes, $layoutUid) : null;

                if (! $previews[0] || ! $previews[1] || ! $currentNode || ! $proposedNode) {
                    continue;
                }

                $preview = [
                    'current' => $this->payloadFor($previews[0], $currentNode),
                    'proposed' => $this->payloadFor($previews[1], $proposedNode),
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
                diff: $isText ? ['current' => (string) self::text($before), 'proposed' => (string) self::text($after)] : null,
                preview: $preview,
                node: $isText ? null : $node,
            );
        }

        return $proposals;
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

    /**
     * @param  string|list<string>  $namespace
     */
    private function compile(ElementInterface $layoutElement, ElementInterface $element, string|array $namespace, ControlMode $mode = ControlMode::Editable): ?UiPayload
    {
        $layout = $layoutElement->getFieldLayout();

        return $layout ? $this->compiler->compile($layout, $element, new UiContext(namespace: $namespace, mode: $mode)) : null;
    }

    /**
     * Returns the nodes of the fields in a form, which can be in tabs. Fields nested in other fields’ controls are
     * left out.
     *
     * @param  list<NodePayload>  $nodes
     * @return list<NodePayload>
     */
    private function fieldNodes(array $nodes): array
    {
        $fields = [];

        foreach ($nodes as $node) {
            $fields = [...$fields, ...($node->control ? [$node] : $this->fieldNodes($node->children ?? []))];
        }

        return $fields;
    }

    /**
     * Returns a value as text, for showing what changed, or `null` if it isn’t text. An empty value is empty text.
     */
    private static function text(mixed $value): ?string
    {
        return $value === null || is_string($value) || is_int($value) || is_float($value) ? (string) $value : null;
    }

    /**
     * Returns a read-only payload of a single field, for a preview. The card labels the field itself, so the field’s
     * own label, status, and action menu are left out, and the payload only has the field’s own values, as it’s the
     * model’s tool result too.
     */
    private function payloadFor(UiPayload $payload, NodePayload $node): UiPayload
    {
        $path = $node->control->path ?? [];
        $values = [];
        data_set($values, $path, data_get($payload->values, $path));

        $node = new NodePayload(
            type: $node->type,
            component: $node->component,
            props: [...$node->props, 'labelSrOnly' => true, 'showStatus' => false],
            uid: $node->uid,
            control: $node->control,
            children: array_values(array_filter(
                $node->children ?? [],
                static fn (NodePayload $child): bool => $child->type !== ActionMenu::class,
            )),
        );

        return new UiPayload(
            scope: $payload->scope,
            refreshable: false,
            nodes: [$node],
            values: $values,
            errors: [],
            globalErrors: [],
        );
    }

    /**
     * Finds a field’s node, which can be in a tab, or at the top level for fields outside of a tab.
     *
     * @param  list<NodePayload>  $nodes
     */
    private function findNode(array $nodes, string $uid): ?NodePayload
    {
        foreach ($nodes as $node) {
            if (($node->props['layoutUid'] ?? $node->uid) === $uid) {
                return $node;
            }

            if ($found = $this->findNode($node->children ?? [], $uid)) {
                return $found;
            }
        }

        return null;
    }
}
