<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Nodes;

use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiPayload;
use Illuminate\Support\Traits\Conditionable;

/**
 * A place in the UI for the page rendering it to fill, with markup or behavior the
 * server can't describe, such as a button that opens a client-side slideout.
 *
 * The Vue renderer fills it from its own slot of the same name. A slot no page fills
 * renders nothing, and the HTML renderer leaves an empty `[data-ui-slot]` placeholder.
 *
 *     Slot::make('mapping-actions')
 *
 *     <UiRenderer :payload="payload">
 *       <template #mapping-actions>…</template>
 *     </UiRenderer>
 *
 * @since 6.0.0
 */
class Slot implements Node
{
    use Conditionable;

    private function __construct(private readonly string $name) {}

    public static function make(string $name): self
    {
        return new self($name);
    }

    public static function renderHtml(NodePayload $node, UiPayload $payload, UiHtmlRenderer $renderer): string
    {
        return Html::tag('div', '', [
            'data-ui-node' => $node->uid,
            'data-ui-slot' => $node->props['name'],
        ]);
    }

    public function component(): string
    {
        return 'craft:slot';
    }

    public function uid(): ?string
    {
        return $this->name;
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
