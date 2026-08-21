<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Contracts;

use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\NodePayload;

interface Node
{
    /**
     * Renders the resolved node for the non-Vue HTML fallback.
     *
     * Implementations should delegate nested nodes and controls back to the
     * supplied renderer so type validation, values, errors, and shared markup
     * remain consistent across the complete form.
     *
     * @param  NodePayload  $node  The resolved node payload, including its props, control, and children.
     * @param  FormPayload  $payload  The complete resolved form payload containing current values and errors.
     * @param  FormHtmlRenderer  $renderer  The renderer coordinating the complete fallback form.
     */
    public static function renderHtml(NodePayload $node, FormPayload $payload, FormHtmlRenderer $renderer): string;

    /**
     * Returns the Vue component registry name used to render this node.
     *
     * The component must be registered before the form is mounted and accept
     * the resolved node, form values, and errors supplied by FormNode.
     */
    public function component(): string;

    /**
     * Returns the node's stable identity when it does not own a control.
     *
     * Pathless nodes require a non-empty UID that is unique within the form.
     * Control-owning nodes may return null because their resolved control path
     * supplies their identity.
     */
    public function uid(): ?string;

    /**
     * Returns node-specific presentation configuration for both renderers.
     *
     * Generic data such as the component name, UID, control, and children
     * belongs to the resolved node payload and should not be repeated here.
     * Every returned value must be JSON-serializable.
     *
     * @return array<string, mixed>
     */
    public function props(): array;

    /**
     * Returns the control owned by this node, if any.
     *
     * The resolver uses the control's path as the node's identity and attaches
     * its resolved payload to this node. Nodes without a control must provide
     * a stable UID instead.
     */
    public function getControl(): ?Control;

    /**
     * Returns this node's ordered child nodes.
     *
     * Children are resolved recursively and may be returned by nodes with or
     * without an owned control.
     *
     * @return list<Node>
     */
    public function children(): array;
}
