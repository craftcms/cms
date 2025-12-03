<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Data;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Dto;

final class Permission extends Dto implements Arrayable
{
    public function __construct(
        public string $key,
        public string $label,
        public ?string $info = null,
        public ?string $warning = null,
        /** @var Collection<Permission> */
        public Collection $nested = new Collection,
    ) {}

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'info' => $this->info,
            'warning' => $this->warning,
            'nested' => $this->nested->keyBy('key')->toArray(),
        ];
    }
}
