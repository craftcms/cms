<?php

namespace craft\ui\concerns;

trait Selectable
{
    protected bool $selected = false;
    protected string $selectedClass = 'sel';

    public function selected(bool $value = true): static
    {
        $this->selected = $value;
        return $this;
    }


    public function getSelected(): bool
    {
        return $this->selected;
    }
}
