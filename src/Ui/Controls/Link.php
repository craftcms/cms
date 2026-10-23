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
 * `value` strings, an optional `label`, and the optional advanced attributes
 * (`urlSuffix`, `target`, `title`, `class`, `id`, `rel`, `ariaLabel`,
 * `download`, and `filename`). Each edit applies as it's made, unless the
 * control is compact, in which case edits are applied from a popover.
 *
 * @since 6.0.0
 */
class Link extends Control
{
    use HasLinkFieldSettings;

    private bool $compact = false;

    /** @var list<string>|null */
    private ?array $registeredTypes = null;

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
            'live' => true,
        ]);
    }

    /**
     * Shows a summary of the link with a button that edits it in a popover,
     * instead of the full link editor.
     */
    public function compact(bool $compact = true): static
    {
        $this->compact = $compact;

        return $this;
    }

    /**
     * Locks links whose type isn’t one of the given link type IDs, so values of
     * link types that are no longer available are kept as they are.
     *
     * @param  list<string>  $typeIds
     */
    public function registeredTypes(array $typeIds): static
    {
        $this->registeredTypes = $typeIds;

        return $this;
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
        return $this->linkFieldSettingsProps() + [
            'compact' => $this->compact,
            'registeredTypes' => $this->registeredTypes,
        ];
    }
}
