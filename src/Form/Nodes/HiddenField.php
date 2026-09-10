<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Nodes;

use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Controls\Hidden;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\NodePayload;
use Illuminate\Support\Traits\Conditionable;

class HiddenField implements Node
{
    use Conditionable;

    private function __construct(private readonly Hidden $control) {}

    /** @param string|list<string> $path */
    public static function make(string|array $path): self
    {
        return new self(Hidden::make($path));
    }

    public function mode(ControlMode|string $mode): static
    {
        $this->control->mode($mode);

        return $this;
    }

    public static function renderHtml(NodePayload $node, FormPayload $payload, FormHtmlRenderer $renderer): string
    {
        return $renderer->renderControl(
            $node->control,
            $payload->values,
            $renderer->id($node->control->path),
            false,
            false,
        );
    }

    public function component(): string
    {
        return 'craft:hidden-field';
    }

    public function uid(): ?string
    {
        return null;
    }

    public function props(): array
    {
        return [];
    }

    public function getControl(): ?Control
    {
        return $this->control;
    }

    public function children(): array
    {
        return [];
    }
}
