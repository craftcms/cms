<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Nodes;

use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\NodePayload;
use Illuminate\Support\Traits\Conditionable;

/**
 * A Control rendered without the surrounding field chrome, for use in a
 * [[Field::actions()]] slot.
 *
 *     Field::make(t('Label'), Text::make('label'))
 *         ->actions(Action::make(
 *             Checkbox::make('labelHidden')->label(t('Hide')),
 *         ));
 */
class Action implements Node
{
    use Conditionable;

    private function __construct(private readonly Control $control) {}

    public static function make(Control $control): self
    {
        return new self($control);
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
            $renderer->errorsFor($payload->errors, $node->control->path) !== [],
            false,
        );
    }

    public function component(): string
    {
        return 'craft:action';
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
