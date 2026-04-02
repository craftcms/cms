<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Components;

use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\View\Components\Concerns\HasId;

class Tooltip extends ViewComponent
{
    use HasId;

    #[\Override]
    protected string $view = 'components.tooltip';

    /**
     * @var string Desired placement for the tooltip.
     *
     * @see placement()
     * @see getPlacement()
     */
    public string $placement = 'top';

    /** @var string|null HTML for the content */
    public ?string $content = null;

    /** @var string|null HTML for the button. */
    public ?string $button = null;

    public function placement(string $value): static
    {
        $this->placement = $value;

        return $this;
    }

    public function button(?string $value = null): static
    {
        $this->button = $value;

        return $this;
    }

    public function content(?string $value = null): static
    {
        $this->content = $value;

        return $this;
    }

    public function getContent(): string
    {
        return app(ContentHtml::class)->parseMarkdown(Html::encode($this->content));
    }

    private function getDefaultIcon(): string
    {
        return Html::tag('craft-icon', '', [
            'name' => 'circle-info',
        ]);
    }

    public function getButton(): string
    {
        if ($this->button) {
            return $this->button;
        }

        return Html::tag('craft-button', $this->getDefaultIcon(), [
            'id' => $this->getId(),
            'appearance' => 'plain',
            'variant' => 'inherit',
            'icon' => true,
            'size' => 'zero',
        ]);
    }

    public static function make(array $config = []): static
    {
        return app(static::class, $config);
    }
}
