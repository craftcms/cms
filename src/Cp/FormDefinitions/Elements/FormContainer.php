<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Elements;

abstract class FormContainer extends FormElement
{
    /** @param list<FormElement> $elements */
    protected function __construct(
        private readonly array $elements,
    ) {
        parent::__construct();
    }

    #[\Override]
    public static function isContainer(): bool
    {
        return true;
    }

    /** @return list<FormElement> */
    #[\Override]
    protected function children(): array
    {
        return $this->elements;
    }
}
