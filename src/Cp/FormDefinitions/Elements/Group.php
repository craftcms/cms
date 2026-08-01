<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Elements;

use CraftCms\Cms\Cp\FormDefinitions\Data\FormElementData;
use CraftCms\Cms\Cp\FormDefinitions\FormDefinition;

class Group extends FormContainer
{
    private ?FormDefinition $definition = null;

    private string $inputNamePrefix = '';

    /** @param list<FormElement> $elements */
    public static function make(array $elements): self
    {
        return new self($elements);
    }

    public static function fromDefinition(FormDefinition $definition, string $inputNamePrefix): self
    {
        $group = new self([]);
        $group->definition = $definition;
        $group->inputNamePrefix = $inputNamePrefix;

        return $group;
    }

    public static function type(): string
    {
        return 'craft:group';
    }

    #[\Override]
    public function toData(): FormElementData
    {
        if ($this->definition === null) {
            return parent::toData();
        }

        return new FormElementData(
            type: static::type(),
            key: $this->elementKey,
            width: $this->width,
            attributes: $this->elementAttributes === [] ? null : $this->elementAttributes,
            children: array_map(
                fn (FormElementData $element): FormElementData => $element->withInputNamePrefix($this->inputNamePrefix),
                $this->definition->toData()->elements,
            ),
            visibleWhen: $this->visibleWhen?->toData(),
        );
    }
}
