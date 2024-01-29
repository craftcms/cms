<?php

namespace craft\ui\components;

use Closure;
use Craft;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\ui\Component;
use craft\ui\concerns\CanBeDisabled;
use craft\ui\concerns\HasId;
use craft\ui\concerns\HasName;
use craft\ui\concerns\HasOrientation;
use craft\ui\concerns\HasValue;
use craft\ui\concerns\HasExtraAttributes;
use Illuminate\View\ComponentAttributeBag;

class Input extends Component
{
    use HasExtraAttributes;
    use HasId;
    use HasName;
    use HasValue;
    use HasOrientation;
    use CanBeDisabled;

    /**
     * @var string The input type
     */
    protected string $type = 'text';

    /**
     * @var string|null The input name
     */
    protected ?string $name = null;


    /**
     * @var string The UI mode of the input.
     */
    protected string $uiMode = 'normal';

    /**
     * @var bool Display input as monospace.
     */
    protected bool $code = false;


    public function type(string $type): static
    {
        $this->type = $type;
        return $this;
    }


    public function name(?string $name): static
    {
        $this->name = $name;
        return $this;
    }


    public function uiMode(string $uiMode): static
    {
        $this->uiMode = $uiMode;
        return $this;
    }

    public function code(bool $value = true): static
    {
        $this->code = $value;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }


    public function getName(): ?string
    {
        return $this->name;
    }

    public function getUiMode(): string
    {
        return $this->uiMode;
    }

    public function getCode(): bool
    {
        return $this->code;
    }

    public function render(): string
    {
        $attributes = (new ComponentAttributeBag())
            ->class([
                'nicetext',
                'text',
                'fullwidth',
                ($this->isDisabled() ? 'disabled' : null),
                ($this->getCode() ? 'code' : null),
                ($this->getUiMode() === 'enlarged' ? 'readable' : null)
            ])
            ->merge([
                'id' => $this->getId(),
                'data-component' => $this->getHandle()
            ])
            ->merge($this->getExtraAttributes());

        return Html::input(
            $this->getType(),
            $this->getName(),
            $this->getValue(),
            $attributes->getAttributes()
        );
    }
}