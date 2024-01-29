<?php

namespace craft\ui\components;

use Craft;
use craft\helpers\Html;
use craft\ui\Component;
use craft\ui\concerns\HasLabel;
use craft\ui\concerns\HasExtraAttributes;
use Illuminate\View\ComponentAttributeBag;

class StatusIndicator extends Component
{
    use HasLabel;
    use HasExtraAttributes;

    /**
     * @var string The status to display.
     * @used-by status()
     * @used-by getStatus()
     */
    protected string $status = 'enabled';

    /**
     * @var ?string The color of the indicator.
     */
    protected ?string $color = null;

    protected string $view = '_ui/status-indicator.twig';

    public function status(string $value): static
    {
        $this->status = $value;
        return $this;
    }

    public function color(?string $value): static
    {
        $this->color = $value;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function getAriaLabel()
    {

    }

    // public function getAttributes(): array
    // {
    //     if ($this->getStatus() === 'draft') {
    //         return [
    //             'data' => ['icon' => 'draft'],
    //             'class' => 'icon',
    //             'role' => 'img',
    //             'aria-label' => $this->getLabel() ? sprintf(
    //                 '%s %s',
    //                 Craft::t('app', 'Status:'),
    //                 Craft::t('app', 'Draft')
    //             ) : null,
    //             'data-component' => $this->getHandle(),
    //         ];
    //     }
    //
    //     if ($this->getStatus() === 'trashed') {
    //         return [
    //             'data' => ['icon' => 'trashed'],
    //             'class' => 'icon',
    //             'role' => 'img',
    //             'aria-label' => $this->getLabel() ? sprintf(
    //                 '%s %s',
    //                 Craft::t('app', 'Status:'),
    //                 Craft::t('app', 'Trashed')
    //             ) : null,
    //             'data-component' => $this->getHandle(),
    //         ];
    //     }
    //
    //     return [
    //         'class' => array_filter([
    //             'status',
    //             $this->getStatus(),
    //             $this->getColor(),
    //         ]),
    //         'role' => 'img',
    //         'aria-label' => $this->getLabel() ? sprintf(
    //             '%s %s',
    //             Craft::t('app', 'Status:'),
    //             $this->getLabel()
    //         ) : null,
    //         'data-component' => $this->getHandle(),
    //     ];
    // }

    public function render(): string
    {
        return Html::tag('span', '', (new ComponentAttributeBag())
            ->class([
                'status',
                $this->getStatus(),
                $this->getColor(),
            ])
            ->merge([
                'role' => 'img',
                'aria-label' => $this->getLabel() ? sprintf(
                    '%s %s',
                    Craft::t('app', 'Status:'),
                    $this->getLabel()
                ) : null,
                'data-component' => $this->getHandle()
            ])
            ->merge($this->getExtraAttributes())
            ->getAttributes()
        );
    }
}
