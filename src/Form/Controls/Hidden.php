<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Support\Html;

class Hidden extends Control
{
    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        return Html::tag('input', '', [
            'type' => 'hidden',
            'id' => $attributes['id'],
            'name' => $attributes['name'],
            'value' => $value,
        ]);
    }

    public function component(): string
    {
        return 'craft:hidden';
    }
}
