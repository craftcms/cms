<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Nodes;

use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\NodePayload;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\Traits\Conditionable;

/**
 * A `<craft-copy-attribute>` — the field handle, shown inline and copyable.
 *
 * Rendered into a {@see Field::actions()} slot for admins with the “Show field
 * handles in edit forms” preference enabled. The 6.x replacement for Craft 5's
 * `_includes/forms/copytextbtn` in `Cp::fieldHtml()`.
 */
class CopyAttribute implements Node
{
    use Conditionable;

    private function __construct(
        private readonly string $uid,
        private readonly string $value,
    ) {}

    /**
     * @param  string  $uid  Stable and unique within the form — control-less
     *                       nodes are keyed by it.
     * @param  string  $value  The attribute name to show and copy.
     */
    public static function make(string $uid, string $value): self
    {
        return new self($uid, $value);
    }

    public static function renderHtml(NodePayload $node, FormPayload $payload, FormHtmlRenderer $renderer): string
    {
        return Html::tag('craft-copy-attribute', '', [
            'value' => $node->props['value'],
            'data-form-node' => $node->uid,
        ]);
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
