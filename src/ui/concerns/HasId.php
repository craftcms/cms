<?php

namespace craft\ui\concerns;

trait HasId
{
    /**
     * @var string|null Raw ID of the element
     */
    protected ?string $id = null;

    /**
     * Set the ID
     *
     * @param string|null $id
     */
    public function id(string $id = null): static
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get the ID
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }
}
