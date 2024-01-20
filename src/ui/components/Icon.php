<?php

namespace craft\ui\components;

use craft\helpers\Cp;
use craft\helpers\Html;
use craft\ui\Component;
use craft\ui\concerns\HasLabel;

class Icon extends Component
{
    use HasLabel;

    protected string $type = 'default';
    protected ?string $icon = null;

    protected array $fontIcons = [
        'assets',
        'gauge',
        'globe',
        'plugin',
        'section',
        'settings',
        'tool',
        'tree',
        'users',
        'ellipsis',
        // TODO
    ];

    public function icon(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function type(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getType(): string
    {
        if (in_array($this->icon, $this->fontIcons)) {
            return 'font';
        }

        // TODO: Probably a better way to handle this
        if (str_starts_with($this->icon, '<svg') || str_starts_with($this->icon, '@') || str_starts_with($this->icon, '/')) {
            return 'svg';
        }

        return $this->type;
    }

    protected function getBody(): string
    {
        return match ($this->getType()) {
            'default' => Cp::renderTemplate('_includes/defaulticon.svg.twig', [
                'label' => $this->getLabel(),
            ]),
            'font' => Html::tag('span', '', [
                'label' => $this->getLabel(),
                'data-icon' => $this->getIcon(),
            ]),
            'svg' => Html::svg($this->getIcon(), true, true),
            default => null
        };
    }

    public function render(): string
    {
        return Html::tag('span', $this->getBody(), [
            'class' => 'icon icon-mask',
            'aria-hidden' => 'true',
        ]);
    }
}
