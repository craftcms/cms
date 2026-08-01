<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Elements;

class Group extends FormContainer
{
    /** @param list<FormElement> $elements */
    public static function make(array $elements): self
    {
        return new self($elements);
    }

    public static function type(): string
    {
        return 'craft:group';
    }
}
