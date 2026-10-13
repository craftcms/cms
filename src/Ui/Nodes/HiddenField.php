<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Nodes;

use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\Controls\Hidden;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiPayload;
use Illuminate\Support\Traits\Conditionable;

/**
 * @since 6.0.0
 */
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

    public static function renderHtml(NodePayload $node, UiPayload $payload, UiHtmlRenderer $renderer): string
    {
        return $renderer->renderControl(
            $node->control,
            $payload->values,
            $renderer->inputId($node->control->path),
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
