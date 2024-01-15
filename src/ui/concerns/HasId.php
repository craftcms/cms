<?php

namespace craft\ui\concerns;

trait HasId
{
    /**
     * @var string|null HTML ID of the element
     */
    protected ?string $id = null;

    public function id(string $id = null): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }
}
