<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Data;

use CraftCms\Cms\Support\Str;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class PermissionGroup implements Arrayable
{
    public function __construct(
        public string $heading,
        /** @var Collection<string, Permission> */
        public Collection $permissions = new Collection,
    ) {}

    public string $handle {
        get => Str::toHandle($this->heading);
    }

    /** @var string[] */
    public array $keys {
        get => $this->permissionKeys($this->permissions);
    }

    public function toArray(): array
    {
        return [
            'heading' => $this->heading,
            'handle' => $this->handle,
            'permissions' => $this->permissions->keyBy('key')->toArray(),
            'keys' => $this->keys,
        ];
    }

    private function permissionKeys(Collection $permissions): array
    {
        return $permissions
            ->flatMap(fn (Permission $permission) => [
                $permission->key,
                ...$this->permissionKeys($permission->nested),
            ])
            ->all();
    }
}
