<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

class FilesystemSelect extends Combobox
{
    protected ?string $createUrl = null;

    protected function tagName(): string
    {
        return 'craft-filesystem-select';
    }

    public function createUrl(?string $createUrl): static
    {
        $this->createUrl = $createUrl;

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            ...parent::hostAttributes(),
            'create-url' => $this->createUrl,
        ];
    }
}
