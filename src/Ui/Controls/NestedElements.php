<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Controls;

use CraftCms\Cms\Element\Data\NestedElementCard;
use CraftCms\Cms\Element\NestedElementManager;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Ui\ControlPayload;
use CraftCms\Cms\Ui\UiHtmlRenderer;

/**
 * A nested element manager whose elements are managed outside the owner form,
 * as cards or an embedded element index. Its payload comes from
 * {@see NestedElementManager::getCardsData()} or {@see NestedElementManager::getIndexData()},
 * so it works for any nested element type: entries, addresses, or a plugin's own.
 *
 * Its cards and index metadata are presentation data, never a submitted field value.
 *
 * @since 6.0.0
 */
class NestedElements extends Control
{
    private string $viewMode = 'cards';

    /** @var array<string, mixed>|null */
    private ?array $manager = null;

    /** @var list<NestedElementCard> */
    private array $cards = [];

    /** @var array<string, mixed>|null */
    private ?array $index = null;

    private ?string $unavailableMessage = null;

    public function component(): string
    {
        return 'craft:nested-elements';
    }

    public function omitNullValue(): bool
    {
        return true;
    }

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, UiHtmlRenderer $renderer): string
    {
        $input = $attributes['name'] === null ? '' : Html::hiddenInput($attributes['name'], '*', [
            'disabled' => true,
            'data-nested-modified' => true,
        ]);

        return Html::tag('craft-nested-elements-control', $input.Html::tag('div', '', [
            'data-nested-mount' => true,
        ]), [
            'id' => $attributes['id'],
            'data-control' => Json::encode($control, JSON_HEX_AMP | JSON_THROW_ON_ERROR),
            'data-scope' => Json::encode($renderer->scope()),
        ]);
    }

    public function viewMode(string $mode): static
    {
        $this->viewMode = $mode;

        return $this;
    }

    /** @param array<string, mixed>|null $manager */
    public function manager(?array $manager): static
    {
        $this->manager = $manager;

        return $this;
    }

    /** @param list<NestedElementCard> $cards */
    public function cards(array $cards): static
    {
        $this->cards = $cards;

        return $this;
    }

    /** @param array<string, mixed>|null $index */
    public function index(?array $index): static
    {
        $this->index = $index;

        return $this;
    }

    public function unavailableMessage(?string $message): static
    {
        $this->unavailableMessage = $message;

        return $this;
    }

    public function props(mixed $value = null): array
    {
        return [
            'viewMode' => $this->viewMode,
            'manager' => $this->manager,
            'cards' => array_map(
                fn (NestedElementCard $card): array => $card->toArray(),
                $this->cards,
            ),
            ...($this->index === null ? [] : ['index' => $this->index]),
            'unavailableMessage' => $this->unavailableMessage,
        ];
    }
}
