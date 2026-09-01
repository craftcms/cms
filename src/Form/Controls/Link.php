<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;

/**
 * A Link Control. Its canonical value is an object with required `type` and
 * `value` strings and optional `label`, `urlSuffix`, and `title` strings.
 *
 * @phpstan-type LinkType array{
 *     id: string,
 *     label: string,
 *     kind: 'custom'|'element'|'text',
 *     prefixes?: list<string>,
 *     pattern?: string,
 *     inputAttributes?: array<string, string>,
 *     elementType?: string,
 *     refHandle?: string,
 *     elementSelectConfig?: array<string, mixed>,
 * }
 */
class Link extends Control
{
    /** @var list<LinkType> */
    private array $types = [];

    private bool $showLabelField = false;

    /** @var list<'urlSuffix'|'title'> */
    private array $advancedFields = [];

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

    /** @param list<LinkType> $types */
    public function types(array $types): static
    {
        $this->types = $types;

        return $this;
    }

    public function showLabelField(bool $showLabelField = true): static
    {
        $this->showLabelField = $showLabelField;

        return $this;
    }

    /** @param list<'urlSuffix'|'title'> $advancedFields */
    public function advancedFields(array $advancedFields): static
    {
        $this->advancedFields = $advancedFields;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return [
            'types' => $this->types,
            'showLabelField' => $this->showLabelField,
            'advancedFields' => $this->advancedFields,
        ];
    }
}
