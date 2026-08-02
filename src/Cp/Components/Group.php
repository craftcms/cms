<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\Forms\Data\FormElementData;
use CraftCms\Cms\Cp\Forms\Form;
use Override;

class Group extends FormContainer
{
    private ?Form $form = null;

    private string $inputNamePrefix = '';

    public static function make(iterable|Closure $children = []): static
    {
        return parent::make()->children($children);
    }

    public static function fromForm(Form $form, string $inputNamePrefix): static
    {
        $group = parent::make();
        $group->form = $form;
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
        if ($this->form !== null) {
            $this->unsupportedOutputOption('form', 'HTML');
        }

        return parent::toHtml();
    }

    /** @return list<FormElementData> */
    #[Override]
    protected function formElementChildren(): array
    {
        if ($this->form === null) {
            return parent::formElementChildren();
        }

        return array_map(
            fn (FormElementData $element): FormElementData => $element->withInputNamePrefix($this->inputNamePrefix),
            $this->form->toData()->elements,
        );
    }
}
