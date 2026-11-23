<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Nodes;

use CraftCms\Cms\Element\EmbeddedElementIndex;
use CraftCms\Cms\Http\ViewModels\EmbeddedIndexViewModel;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiPayload;
use Illuminate\Support\Traits\Conditionable;

/**
 * An element index listing the elements matching an {@see EmbeddedElementIndex}’s criteria.
 *
 * @since 6.0.0
 */
class ElementIndex implements Node
{
    use Conditionable;

    public function __construct(
        private readonly string $uid,
        private readonly EmbeddedElementIndex $index,
    ) {}

    public static function make(string $uid, EmbeddedElementIndex $index): self
    {
        return new self($uid, $index);
    }

    public static function renderHtml(NodePayload $node, UiPayload $payload, UiHtmlRenderer $renderer): string
    {
        return Html::tag('div', '', ['id' => $node->uid]);
    }

    public function component(): string
    {
        return 'craft:element-index';
    }

    public function uid(): ?string
    {
        return $this->uid;
    }

    public function props(): array
    {
        return [
            'token' => $this->index->token(),
            'elementType' => $this->index->elementType(),
            'storageKey' => $this->index->getConfig()['storageKey'] ?? null,
            'initial' => EmbeddedIndexViewModel::forDefinition($this->index)->payload(),
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
