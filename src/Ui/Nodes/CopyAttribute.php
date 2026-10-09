<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Nodes;

use CraftCms\Cms\Cp\Components\CopyAttribute as CopyAttributeComponent;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiPayload;
use Illuminate\Support\Traits\Conditionable;

/**
 * A `<craft-copy-attribute>` — the field handle, shown inline and copyable.
 *
 * Rendered into a {@see Field::actions()} slot for admins with the “Show field
 * handles in edit forms” preference enabled. The 6.x replacement for Craft 5's
 * `_includes/forms/copytextbtn` in `Cp::fieldHtml()`.
 *
 * @since 6.0.0
 */
class CopyAttribute implements Node
{
    use Conditionable;

    private function __construct(
        private readonly string $uid,
        private readonly string $value,
    ) {}

    /**
     * @param  string  $uid  Stable and unique within the UI — control-less
     *                       nodes are keyed by it.
     * @param  string  $value  The attribute name to show and copy.
     */
    public static function make(string $uid, string $value): self
    {
        return new self($uid, $value);
    }

    public static function renderHtml(NodePayload $node, UiPayload $payload, UiHtmlRenderer $renderer): string
    {
        return CopyAttributeComponent::make()
            ->value($node->props['value'])
            ->attributes(['data-ui-node' => $node->uid])
            ->toHtml();
    }

    public function component(): string
    {
        return 'craft:copy-attribute';
    }

    public function uid(): ?string
    {
        return $this->uid;
    }

    public function props(): array
    {
        return [
            'value' => $this->value,
        ];
    }

    public function getControl(): ?Control
    {
        return null;
    }

    public function children(): array
    {
        return [];
    }
}
