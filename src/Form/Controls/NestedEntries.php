<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Element\Data\NestedElementCard;
use CraftCms\Cms\Element\NestedElementManager;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;

use function CraftCms\Cms\t;

/**
 * A nested-entry manager whose elements are managed outside the owner form.
 * Its cards and index metadata are presentation data, never a submitted field value.
 */
class NestedEntries extends Control
{
    private string $viewMode = 'cards';

    /** @var array<string, mixed>|null */
    private ?array $manager = null;

    /** @var list<NestedElementCard> */
    private array $cards = [];

    /** @var array<string, mixed>|null */
    private ?array $index = null;

    private ?string $indexHtml = null;

    private ?string $unavailableMessage = null;

    public function component(): string
    {
        return 'craft:nested-entries';
    }

    public function omitNullValue(): bool
    {
        return true;
    }

    /**
     * Renders the existing nested element manager markup for server-rendered forms.
     */
    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        if (($control->props['viewMode'] ?? null) === 'index' && isset($control->props['indexHtml'])) {
            return $control->props['indexHtml'];
        }

        $manager = $control->props['manager'] ?? null;
        $unavailableMessage = $control->props['unavailableMessage'] ?? null;

        if (is_string($unavailableMessage) || ! is_array($manager)) {
            return Html::tag('div', Html::encode((string) $unavailableMessage), [
                'class' => 'pane no-border zilch small',
            ]);
        }

        /** @var list<array<string, mixed>> $cards */
        $cards = $control->props['cards'] ?? [];
        $elementHtml = app(ElementHtml::class);
        $html = '';

        if ($cards !== []) {
            $html .= Html::ul()->items(...array_map(
                fn (array $card) => Html::li($elementHtml->composeElementCardHtml(
                    $card['cardAttributes'],
                    $card['cardHeaderHtml'],
                    $card['cardActionsHtml'],
                    $card['cardContentHtml'],
                    $card['cardFooterHtml'],
                    $card['cardThumbHtml'],
                    $card['thumbAlignment'],
                    (bool) ($manager['selectable'] ?? false),
                ))->encode(false),
                $cards,
            ))->class(
                'elements',
                ($manager['showInGrid'] ?? false) ? 'card-grid' : 'cards',
                ($manager['prevalidate'] ?? false) ? 'prevalidate' : '',
            )->render();
        }

        $html .= Html::tag('craft-empty', t('Nothing yet.'), [
            'class' => $cards === [] ? [] : ['hidden'],
        ]);

        return Html::tag('craft-nested-element-manager', Html::tag('div', $html, [
            'id' => $attributes['id'],
            'class' => 'nested-element-cards grid gap-2',
        ]), [
            'element-type' => $manager['elementType'],
            // The legacy manager marks the field modified through this input name.
            'settings' => Json::encode(NestedElementManager::htmlManagerSettings([
                ...$manager,
                'baseInputName' => $attributes['name'],
            ])),
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

    public function indexHtml(?string $html): static
    {
        $this->indexHtml = $html;

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
            ...($this->indexHtml === null ? [] : ['indexHtml' => $this->indexHtml]),
            'unavailableMessage' => $this->unavailableMessage,
        ];
    }
}
