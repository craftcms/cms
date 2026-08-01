<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Data;

use JsonSerializable;

readonly class PluginData implements JsonSerializable
{
    public function __construct(
        public string $handle,
        public string $name,
        public string $packageName,
    ) {}

    /** @return array{handle: string, name: string, packageName: string} */
    public function jsonSerialize(): array
    {
        return [
            'handle' => $this->handle,
            'name' => $this->name,
            'packageName' => $this->packageName,
        ];
    }

    public function equals(?self $other): bool
    {
        return $other !== null
            && $this->handle === $other->handle
            && $this->name === $other->name
            && $this->packageName === $other->packageName;
    }
}
