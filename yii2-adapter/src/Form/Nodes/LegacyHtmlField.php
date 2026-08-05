<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Form\Nodes;

use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\NodePayload;
use InvalidArgumentException;

class LegacyHtmlField implements Node
{
    public function __construct(private readonly Control $control)
    {
    }

    public static function renderHtml(NodePayload $node, FormPayload $payload, FormHtmlRenderer $renderer): string
    {
        if ($node->control === null) {
            throw new InvalidArgumentException('Legacy HTML Field Nodes require a Control payload.');
        }

        return $renderer->renderControl(
            $node->control,
            $payload->values,
            $renderer->id($node->control->path),
            $renderer->errorsFor($payload->errors, $node->control->path) !== [],
            false,
        );
    }

    public function component(): string
    {
        return 'craft-legacy:html-field';
    }

    public function uid(): ?string
    {
        return null;
    }

    public function props(): array
    {
        return [];
    }

    public function getControl(): Control
    {
        return $this->control;
    }

    public function children(): array
    {
        return [];
    }
}
