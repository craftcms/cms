<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\FormDefinitions\Data\FormElementData;
use CraftCms\Cms\Cp\FormDefinitions\FormDefinition;
use Override;

class Group extends FormContainer
{
    private ?FormDefinition $definition = null;

    private string $inputNamePrefix = '';

    public static function make(iterable|Closure $children = []): static
    {
        return parent::make()->children($children);
    }

    public static function fromDefinition(FormDefinition $definition, string $inputNamePrefix): static
    {
        $group = parent::make();
        $group->definition = $definition;
        $group->inputNamePrefix = $inputNamePrefix;

        return $group;
    }

    public static function formElementType(): string
    {
        return 'craft:group';
    }

    #[Override]
    protected function tagName(): string
    {
        return 'craft-field-group';
    }

    #[Override]
    public function toHtml(): string
    {
        if ($this->definition !== null) {
            $this->unsupportedOutputOption('definition', 'HTML');
        }

        return parent::toHtml();
    }

    /** @return list<FormElementData> */
    #[Override]
    protected function formElementChildren(): array
    {
        if ($this->definition === null) {
            return parent::formElementChildren();
        }

        return array_map(
            fn (FormElementData $element): FormElementData => $element->withInputNamePrefix($this->inputNamePrefix),
            $this->definition->toData()->elements,
        );
    }
}
