<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\LinkTypes;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Component\Concerns\ConfigurableComponent;
use CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface;
use CraftCms\Cms\Cp\FormDefinitions\Contracts\FormElement;
use CraftCms\Cms\Cp\FormDefinitions\FormDefinition;
use CraftCms\Cms\Field\Link;

abstract class BaseLinkType extends Component implements ConfigurableComponentInterface
{
    use ConfigurableComponent;

    /**
     * Returns the link type’s unique identifier, which will be stored within
     * Link fields’ [[\craft\fields\Link::types]] settings.
     */
    abstract public static function id(): string;

    public function getSettingsFormDefinition(bool $readOnly): ?FormDefinition
    {
        $elements = $this->settingsFormElements($readOnly);

        return $elements === [] ? null : FormDefinition::make($elements);
    }

    /** @return list<FormElement> */
    protected function settingsFormElements(bool $readOnly): array
    {
        return [];
    }

    /**
     * Returns whether the given value is supported by this link type.
     */
    abstract public function supports(string $value): bool;

    /**
     * Normalizes a posted link value.
     */
    public function normalizeValue(string $value): string
    {
        return $value;
    }

    /**
     * Renders a value for the front end.
     */
    public function renderValue(string $value): string
    {
        return $value;
    }

    /**
     * Returns the default link label for [[\CraftCms\Cms\Field\Data\LinkData::getLabel()]].
     */
    abstract public function linkLabel(string $value): string;

    /**
     * Returns the default download filename for [[\CraftCms\Cms\Field\Data\LinkData::getFilename()]].
     */
    public function filename(string $value): ?string
    {
        return null;
    }

    /**
     * Returns configuration that JavaScript link pickers can use to render
     * this link type without relying on the Link field's server-rendered input.
     */
    public function pickerConfig(): array
    {
        return [
            'id' => static::id(),
            'label' => static::displayName(),
            'kind' => 'custom',
        ];
    }

    /**
     * Returns the input HTML that should be shown when this link type is selected.
     *
     * @param  Link  $field  The Link field
     * @param  string|null  $value  The current value, if this link type was previously selected.
     * @param  string  $containerId  The ID of the input’s container div.
     */
    abstract public function inputHtml(Link $field, ?string $value, string $containerId): string;

    abstract public function validateValue(string $value, ?string &$error = null): bool;

    public function isValueEmpty(string $value): bool
    {
        return empty($value);
    }
}
