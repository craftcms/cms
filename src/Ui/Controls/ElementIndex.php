<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Controls;

use CraftCms\Cms\Element\EmbeddedElementIndex;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\ControlPayload;
use CraftCms\Cms\Ui\UiHtmlRenderer;

/**
 * A read-only element index that lists the elements matching an {@see EmbeddedElementIndex}’s criteria.
 *
 * Its rows are presentation data, never a submitted field value.
 *
 * @since 6.0.0
 */
class ElementIndex extends Control
{
    private ?string $token = null;

    private ?string $elementType = null;

    private ?string $storageKey = null;

    /** @var array<string, mixed>|null */
    private ?array $initial = null;

    public function component(): string
    {
        return 'craft:element-index';
    }

    public function omitNullValue(): bool
    {
        return true;
    }

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, UiHtmlRenderer $renderer): string
    {
        return Html::tag('div', '', ['id' => $attributes['id']]);
    }

    public function token(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function elementType(string $elementType): static
    {
        $this->elementType = $elementType;

        return $this;
    }

    public function storageKey(?string $storageKey): static
    {
        $this->storageKey = $storageKey;

        return $this;
    }

    /** @param array<string, mixed> $initial */
    public function initial(array $initial): static
    {
        $this->initial = $initial;

        return $this;
    }

    public function props(mixed $value = null): array
    {
        return [
            'token' => $this->token,
            'elementType' => $this->elementType,
            'storageKey' => $this->storageKey,
            'initial' => $this->initial,
        ];
    }
}
