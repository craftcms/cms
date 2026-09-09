<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Nodes;

use CraftCms\Cms\Component\MissingComponents;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\NodePayload;
use CraftCms\Cms\Support\Arr;
use Illuminate\Support\Traits\Conditionable;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class Missing implements Node
{
    use Conditionable;

    private function __construct(
        private readonly string $uid,
        private readonly string $provider,
    ) {}

    public static function renderHtml(NodePayload $node, FormPayload $payload, FormHtmlRenderer $renderer): string
    {
        return template('_special/missing-component', $node->props + [
            'attributes' => ['data-form-node' => $node->uid],
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
            t('Form Node provider [{provider}] is unavailable.', [
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
