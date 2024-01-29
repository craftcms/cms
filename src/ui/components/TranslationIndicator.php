<?php

namespace craft\ui\components;

use Craft;
use craft\helpers\Html;
use craft\ui\Component;

class TranslationIndicator extends Component
{
    protected ?string $description = 'This field is translatable.';

    public function description(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription(string $category = 'app'): ?string
    {
        if ($category) {
            return Craft::t($category, $this->description);
        }

        return $this->description;
    }

    public function render(): string
    {
        return Html::tag('span', '', [
            'class' => ['t9n-indicator'],
            'title' => $this->getDescription(),
            'data-icon' => 'language',
            'aria-label' => $this->getDescription(),
            'role' => 'img',
        ]);
    }

}