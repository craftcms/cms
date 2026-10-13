<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Controls;

use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Ui\ControlPayload;
use CraftCms\Cms\Ui\Controls\Concerns\HasLinkFieldSettings;
use CraftCms\Cms\Ui\UiHtmlRenderer;

/**
 * A Link Control. Its canonical value is an object with required `type` and
 * `value` strings and optional `label`, `urlSuffix`, and `title` strings.
 *
 * @since 6.0.0
 */
class Link extends Control
{
    use HasLinkFieldSettings;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, UiHtmlRenderer $renderer): string
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

    /** @return array<string, mixed> */
    #[\Override]
    public function emptyValue(): mixed
    {
        return [];
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return $this->linkFieldSettingsProps();
    }
}
