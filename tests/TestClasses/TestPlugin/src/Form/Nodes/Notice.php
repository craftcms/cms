<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\TestPlugin\src\Form\Nodes;

use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiPayload;

class Notice implements Node
{
    public function __construct(
        private readonly string $uid,
        private readonly string $message,
    ) {}

    public static function renderHtml(NodePayload $node, UiPayload $payload, UiHtmlRenderer $renderer): string
    {
        return Html::tag('p', Html::encode((string) $node->props['message']), [
            'data-ui-node' => $node->uid,
            'data-test-plugin-notice' => true,
        ]);
    }

    public function component(): string
    {
        return 'test-plugin:notice';
    }

    public function uid(): ?string
    {
        return $this->uid;
    }

    public function props(): array
    {
        return ['message' => $this->message];
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
