<?php

namespace craft\ui\components;

use craft\helpers\Html;
use craft\ui\Component;
use craft\ui\concerns\HasLabel;
use Filament\Support\Concerns\HasExtraAttributes;
use Filament\Support\Concerns\HasIcon;
use Illuminate\View\ComponentAttributeBag;

class Button extends Component
{
    use HasLabel;
    use HasExtraAttributes;
    use HasIcon;

    protected static array $variants = [
        'default',
        'secondary',
        'submit',
    ];

    protected string $variant = 'default';

    protected string $state = 'idle';

    public function secondary(): static
    {
        $this->variant = 'secondary';
        return $this;
    }

    public function variant(string $variant): static
    {
        if (in_array($variant, self::$variants)) {
            $this->variant = $variant;
        }

        return $this;
    }

    public function getVariant(): ?string
    {
        return $this->variant !== 'default' ? $this->variant : null;
    }

    public function render(): string
    {
        return Html::button($this->getLabel(), (new ComponentAttributeBag())
            ->class([
                'btn',
                $this->getVariant(),
            ])
            ->merge(['data-icon' => $this->getIcon()])
            ->merge($this->getExtraAttributes(), false)
            ->getAttributes()
        );
    }
}
