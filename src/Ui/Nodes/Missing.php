<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Nodes;

use CraftCms\Cms\Component\MissingComponents;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiPayload;
use Illuminate\Support\Traits\Conditionable;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * @since 6.0.0
 */
class Missing implements Node
{
    use Conditionable;

    private function __construct(
        private readonly string $uid,
        private readonly string $provider,
    ) {}

    public static function renderHtml(NodePayload $node, UiPayload $payload, UiHtmlRenderer $renderer): string
    {
        return template('_special/missing-component', $node->props + [
            'attributes' => ['data-ui-node' => $node->uid],
            'formId' => null,
        ]);
    }

    public static function make(string $uid, string $provider): self
    {
        return new self($uid, $provider);
    }

    public function component(): string
    {
        return 'craft:missing-node';
    }

    public function uid(): ?string
    {
        return $this->uid;
    }

    public function props(): array
    {
        $presentation = app(MissingComponents::class)->resolve(
            $this->provider,
            t('UI Node provider [{provider}] is unavailable.', [
                'provider' => $this->provider,
            ]),
        );

        return ['provider' => $this->provider] + Arr::only($presentation, [
            'error',
            'pluginName',
            'action',
        ]);
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
