<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use InvalidArgumentException;

use function CraftCms\Cms\t;

/**
 * Selects an ordered list of element IDs through Craft's element selector.
 */
class ElementSelect extends Control
{
    #[\Override]
    protected mixed $value = [];

    /** @var class-string<ElementInterface>|null */
    private ?string $elementType = null;

    /** @var list<string>|null */
    private ?array $sources = null;

    /** @var array<string, mixed> */
    private array $criteria = [];

    private ?string $selectionLabel = null;

    private ?int $limit = null;

    private bool $showSiteMenu = false;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        $editable = $attributes['name'] !== null;

        return FormFields::elementSelectHtml([
            'id' => $attributes['id'],
            'name' => $attributes['name'],
            'elements' => self::elements($control->props['elementType'], $value),
            'elementType' => $control->props['elementType'],
            'sources' => $control->props['sources'],
            'criteria' => $control->props['criteria'],
            'selectionLabel' => $control->props['selectionLabel'],
            'limit' => $control->props['limit'],
            'showSiteMenu' => $control->props['showSiteMenu'],
            'allowAdd' => $editable,
            'allowRemove' => $editable,
            'sortable' => false,
            'disabled' => ! $editable,
            'useCustomElement' => true,
            'customElement' => $control->props['customElement'],
        ]);
    }

    public function component(): string
    {
        return 'craft:element-select';
    }

    /** @param class-string<ElementInterface> $elementType */
    public function elementType(string $elementType): static
    {
        if (! is_a($elementType, ElementInterface::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'Element type [%s] must implement %s.',
                $elementType,
                ElementInterface::class,
            ));
        }

        $this->elementType = $elementType;

        return $this;
    }

    /** @param list<string>|null $sources */
    public function sources(?array $sources): static
    {
        $this->sources = $sources;

        return $this;
    }

    /** @param array<string, mixed> $criteria */
    public function criteria(array $criteria): static
    {
        $this->criteria = $criteria;

        return $this;
    }

    public function selectionLabel(string $selectionLabel): static
    {
        $this->selectionLabel = $selectionLabel;

        return $this;
    }

    public function limit(?int $limit): static
    {
        if ($limit !== null && $limit < 1) {
            throw new InvalidArgumentException('Element selection limits must be at least 1.');
        }

        $this->limit = $limit;

        return $this;
    }

    public function showSiteMenu(bool $showSiteMenu = true): static
    {
        $this->showSiteMenu = $showSiteMenu;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        if ($this->elementType === null) {
            throw new InvalidArgumentException('ElementSelect Controls require an element type.');
        }

        $elements = self::elements($this->elementType, $value);

        return [
            'elementType' => $this->elementType,
            'customElement' => self::customElement($this->elementType),
            'elements' => array_map(fn (ElementInterface $element): array => [
                'id' => $element->getId(),
                'label' => $element->getUiLabel(),
                'siteId' => $element->siteId,
            ], $elements),
            'sources' => $this->sources,
            'criteria' => $this->criteria,
            'selectionLabel' => $this->selectionLabel ?? t('Choose'),
            'limit' => $this->limit,
            'showSiteMenu' => $this->showSiteMenu,
        ];
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return list<ElementInterface>
     */
    private static function elements(string $elementType, mixed $value): array
    {
        if (! is_array($value) || $value === []) {
            return [];
        }

        return $elementType::find()->id(array_values($value))->fixedOrder()->all();
    }

    /** @param class-string<ElementInterface> $elementType */
    private static function customElement(string $elementType): string
    {
        if (is_a($elementType, Asset::class, true)) {
            return 'craft-asset-select-input';
        }

        if (is_a($elementType, Entry::class, true)) {
            return 'craft-entry-select-input';
        }

        return 'craft-element-select-input';
    }
}
