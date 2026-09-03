<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Nodes;

use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\NodePayload;
use Illuminate\Support\Traits\Conditionable;

/**
 * Marks where the form stops being fixed and starts depending on a control that
 * {@see \CraftCms\Cms\Form\Controls\Control::rebuildsForm() rebuilds it} — a
 * field's type, say.
 *
 * While such a refresh is in flight the renderer stands a loading indicator
 * here and drops the nodes after it, so the part that's about to be replaced
 * reads as pending rather than as stale settings that are still current.
 * Renders nothing otherwise, and nothing at all in the HTML fallback, which has
 * no refresh to wait on.
 */
class Loader implements Node
{
    use Conditionable;

    public function __construct(private readonly string $uid) {}

    public static function renderHtml(NodePayload $node, FormPayload $payload, FormHtmlRenderer $renderer): string
    {
        return '';
    }

    public static function make(string $uid): self
    {
        return new self($uid);
    }

    public function component(): string
    {
        return 'craft:loader';
    }

    public function uid(): ?string
    {
        return $this->uid;
    }

    public function props(): array
    {
        return [];
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
