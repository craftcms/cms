<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Controls;

use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\ControlPayload;
use CraftCms\Cms\Ui\UiHtmlRenderer;

/**
 * @since 6.0.0
 */
class Hidden extends Control
{
    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, UiHtmlRenderer $renderer): string
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
