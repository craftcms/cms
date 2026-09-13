<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\Controls\Concerns\HasLinkFieldSettings;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;

/**
 * A Link Control. Its canonical value is an object with required `type` and
 * `value` strings and optional `label`, `urlSuffix`, and `title` strings.
 */
class Link extends Control
{
    use HasLinkFieldSettings;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        $value = is_array($value) ? $value : [];

        return Html::tag('craft-link-field', '', [
            'types' => Json::encode($control->props['types'] ?? []),
            'model-value' => Json::encode($value),
            'name' => $attributes['name'],
            'show-label-field' => $control->props['showLabelField'] ?? false,
            'advanced-fields' => Json::encode($control->props['advancedFields'] ?? []),
            'disabled' => $attributes['name'] === null,
        ]);
    }

    public function component(): string
    {
        return 'craft:link';
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return $this->linkFieldSettingsProps();
    }
}
