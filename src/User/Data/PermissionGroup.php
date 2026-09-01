<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Data;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

/** @implements Arrayable<string, mixed> */
class PermissionGroup implements Arrayable
{
    public function __construct(
        public string $handle,
        public string $heading,
        /** @var Collection<int, Permission> */
        public Collection $permissions = new Collection,
    ) {}

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

    /**
     * @param  Collection<int, Permission>  $permissions
     * @return string[]
     */
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
