<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Validation\Rules\HandleRule;
use Override;

class AssetProcessor extends Component
{
    public ?string $uid = null;

    public string $name = '';

    public string $handle = '';

    public string $driver = '';

    /** @var array<string, mixed> */
    public array $settings = [];

    /** @return array{name:string,handle:string,driver:string,settings:array<string,mixed>} */
    public function getConfig(): array
    {
        return [
            'name' => $this->name,
            'handle' => $this->handle,
            'driver' => $this->driver,
            'settings' => $this->settings,
        ];
    }

    #[Override]
    public function getRules(): array
    {
        return [
            'uid' => ['nullable', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'handle' => ['required', 'string', 'max:255', new HandleRule],
            'driver' => ['required', 'string'],
            'settings' => ['array'],
        ];
    }

    public function __debugInfo(): array
    {
        return [
            'uid' => $this->uid,
            'name' => $this->name,
            'handle' => $this->handle,
            'driver' => $this->driver,
            'settings' => '[redacted]',
        ];
    }
}
