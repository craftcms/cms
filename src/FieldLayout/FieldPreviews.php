<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\BaseOptionsField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\ControlPayload;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\NestedUiPayload;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\Nodes\ActionMenu;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use Twig\Markup;

/**
 * Compiles element forms and previews their fields on their own, for showing a field’s value before and after a
 * change: the AI assistant’s proposed changes, and the “Compare changes” field action.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class FieldPreviews
{
    public function __construct(
        private FieldLayoutCompiler $compiler,
    ) {}

    /**
     * Compiles an element’s form with another element’s field layout, such as a copy’s, or the element a draft is of.
     *
     * @param  string|list<string>  $namespace
     */
    public function compile(ElementInterface $layoutElement, ElementInterface $element, string|array $namespace, ControlMode $mode = ControlMode::Editable): ?UiPayload
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
    public function fieldNodes(array $nodes): array
    {
        $fields = [];

        foreach ($nodes as $node) {
            $fields = [...$fields, ...($node->control ? [$node] : $this->fieldNodes($node->children ?? []))];
        }

        return $fields;
    }

    /**
     * Finds a field’s node by its layout element’s UID. It can be in a tab, or at the top level for fields outside of
     * a tab.
     *
     * @param  list<NodePayload>  $nodes
     */
    public function findNode(array $nodes, string $uid): ?NodePayload
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

    /**
     * Returns a read-only payload of a single field, for a preview. Whatever shows the preview labels the field
     * itself, so the field’s own label and status are left out, and the payload only has the field’s own values.
     * There’s nothing to act on in a preview, so action menus are left out, including nested fields’.
     */
    public function payloadFor(UiPayload $payload, NodePayload $node): UiPayload
    {
        $path = $node->control->path ?? [];
        $values = [];
        data_set($values, $path, data_get($payload->values, $path));

        $node = $this->withoutActionMenus(new NodePayload(
            type: $node->type,
            component: $node->component,
            props: [...$node->props, 'labelSrOnly' => true, 'showStatus' => false],
            uid: $node->uid,
            control: $node->control,
            children: $node->children,
        ));

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
     * Returns a node without action menus, in its children or in its control’s nested UIs, such as a Matrix field’s
     * entries’ fields.
     */
    private function withoutActionMenus(NodePayload $node): NodePayload
    {
        $control = $node->control;

        if ($control !== null && $control->uis !== []) {
            $control = new ControlPayload(
                type: $control->type,
                component: $control->component,
                props: $control->props,
                path: $control->path,
                mode: $control->mode,
                deltaGroup: $control->deltaGroup,
                uis: array_map(fn (NestedUiPayload $ui): NestedUiPayload => new NestedUiPayload(
                    scope: $ui->scope,
                    refreshable: $ui->refreshable,
                    nodes: array_map($this->withoutActionMenus(...), $ui->nodes),
                ), $control->uis),
                reactive: $control->reactive,
                emptyValue: $control->emptyValue,
                nestsUis: $control->nestsUis,
                omitNullValue: $control->omitNullValue,
            );
        }

        return new NodePayload(
            type: $node->type,
            component: $node->component,
            props: $node->props,
            uid: $node->uid,
            control: $control,
            children: $node->children === null ? null : array_map($this->withoutActionMenus(...), array_values(array_filter(
                $node->children,
                static fn (NodePayload $child): bool => $child->type !== ActionMenu::class,
            ))),
        );
    }

    /**
     * Returns a text diff of a field’s change, or `null` to preview the change with the field’s controls instead:
     * when either value isn’t text, or is an options field’s encoded value, which its control shows by its label.
     * HTML, such as a rich text field’s, is diffed by its text, unless that still has tags or only the markup
     * changed. Other text with tags in it isn’t diffed either, as the activity timeline doesn’t list it. Two `null`s
     * aren’t diffed: fields that load their own values, such as Matrix fields shown as cards, have none in the form.
     *
     * @param  ElementInterface  $element  An element with the field, to tell whether its values are HTML.
     * @return array{current: string, proposed: string}|null
     */
    public static function diff(mixed $before, mixed $after, ElementInterface $element, ?FieldLayoutElement $layoutElement): ?array
    {
        $field = $layoutElement instanceof CustomField ? $layoutElement->getField() : null;

        if (($before === null && $after === null) || $field instanceof BaseOptionsField) {
            return null;
        }

        $html = $field !== null && $element->getFieldValue((string) $field->handle) instanceof Markup;
        $current = self::text($before, $html);
        $proposed = self::text($after, $html);

        if ($current === null || $proposed === null || ($current === $proposed && $before !== $after)) {
            return null;
        }

        return ['current' => $current, 'proposed' => $proposed];
    }

    /**
     * Returns a value as text, or `null` if it isn’t text, or has tags in it. An empty value is empty text.
     */
    private static function text(mixed $value, bool $html): ?string
    {
        if ($value === null || is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $text = $html ? Html::toText($value) : $value;

        return Html::isPlainText($text) ? $text : null;
    }
}
