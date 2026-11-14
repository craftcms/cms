<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Nodes;

use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\Controls\FieldLayoutDesigner;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiPayload;
use Illuminate\Support\Traits\Conditionable;

/**
 * A named place in the form where something rendered elsewhere can move to,
 * such as the card view designer of a field layout designer whose
 * {@see FieldLayoutDesigner::cardViewDesignerOutlet()}
 * names it.
 *
 * Outlets are only filled when the form is rendered by Vue; server-rendered
 * HTML leaves them empty, and whatever would fill them renders in place.
 *
 * @since 6.0.0
 */
class Outlet implements Node
{
    use Conditionable;

    private function __construct(
        private readonly string $uid,
        private readonly string $name,
    ) {}

    public static function renderHtml(NodePayload $node, UiPayload $payload, UiHtmlRenderer $renderer): string
    {
        return Html::tag('div', '', [
            'data-ui-node' => $node->uid,
            'data-ui-outlet' => $node->props['name'],
        ]);
    }

    public static function make(string $uid, string $name): self
    {
        return new self($uid, $name);
    }

    public function component(): string
    {
        return 'craft:outlet';
    }

    public function uid(): ?string
    {
        return $this->uid;
    }

    public function props(): array
    {
        return ['name' => $this->name];
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
