<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\TestPlugin\src\Form\Controls;

use CraftCms\Cms\Cp\Components\Input;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\Controls\Control;
use CraftCms\Cms\Form\FormHtmlRenderer;

class Slug extends Control
{
    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        return Input::make()
            ->id($attributes['id'])
            ->name($attributes['name'])
            ->value($value === null ? null : (string) $value)
            ->placeholder((string) $control->props['placeholder'])
            ->disabled($attributes['disabled'])
            ->readOnly($attributes['readonly'])
            ->inputAttributes([
                'required' => $attributes['required'],
                'aria' => ['invalid' => $attributes['aria']['invalid'] ?? null],
                'data-test-plugin-control' => true,
            ])
            ->toHtml();
    }

    public function component(): string
    {
        return 'test-plugin:slug';
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return ['placeholder' => 'plugin-slug'];
    }
}
