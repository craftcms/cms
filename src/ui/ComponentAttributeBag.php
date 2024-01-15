<?php

namespace craft\ui;

class ComponentAttributeBag
{
    /**
     * The raw array of attributes.
     *
     * @var array
     */
    protected array $attributes = [];

    /**
     * Create a new component attribute bag instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }
}
