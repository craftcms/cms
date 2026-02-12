<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Data;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

final class PermissionGroup implements Arrayable
{
    public function __construct(
        public string $heading,
        /** @var Collection<Permission> */
        public Collection $permissions = new Collection,
    ) {}

    public function toArray(): array
    {
        return [
            'heading' => $this->heading,
            'permissions' => $this->permissions->keyBy('key')->toArray(),
        ];
    }
}
