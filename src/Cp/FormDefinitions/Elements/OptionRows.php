<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Elements;

class OptionRows extends InputElement
{
    private bool $multipleDefaults = false;

    private bool $optgroups = false;

    private bool $icons = false;

    private bool $colors = false;

    public static function type(): string
    {
        return 'craft:option-rows';
    }

    public function multipleDefaults(bool $multipleDefaults = true): static
    {
        $this->multipleDefaults = $multipleDefaults;

        return $this;
    }

    public function optgroups(bool $optgroups = true): static
    {
        $this->optgroups = $optgroups;

        return $this;
    }

    public function icons(bool $icons = true): static
    {
        $this->icons = $icons;

        return $this;
    }

    public function colors(bool $colors = true): static
    {
        $this->colors = $colors;

        return $this;
    }

    /** @return array<string, mixed> */
    #[\Override]
    protected function props(): array
    {
        return array_filter([
            'multipleDefaults' => $this->multipleDefaults ?: null,
            'optgroups' => $this->optgroups ?: null,
            'icons' => $this->icons ?: null,
            'colors' => $this->colors ?: null,
        ], fn (mixed $value): bool => $value !== null);
    }
}
