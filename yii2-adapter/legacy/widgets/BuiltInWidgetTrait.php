<?php

declare(strict_types=1);

namespace craft\widgets;

use craft\base\WidgetTrait;
use ReflectionMethod;

trait BuiltInWidgetTrait
{
    use WidgetTrait {
        component as private htmlComponent;
        props as private htmlProps;
    }

    public function component(): ?string
    {
        return $this->hasHtmlOverride() ? $this->htmlComponent() : parent::component();
    }

    public function props(): array
    {
        return $this->hasHtmlOverride() ? $this->htmlProps() : parent::props();
    }

    /**
     * Leaves out the colspan and color that `WidgetTrait` declares on this
     * concrete class. They're saved in columns of their own, which saved
     * settings would otherwise override.
     *
     * @return string[]
     */
    public function settingsAttributes(): array
    {
        return array_values(array_diff(parent::settingsAttributes(), ['colspan', 'color']));
    }

    private function hasHtmlOverride(): bool
    {
        return new ReflectionMethod($this, 'getBodyHtml')->getDeclaringClass()->isSubclassOf(
            new ReflectionMethod($this, 'component')->getDeclaringClass()->getName(),
        );
    }
}
