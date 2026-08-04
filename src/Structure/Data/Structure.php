<?php

declare(strict_types=1);

namespace CraftCms\Cms\Structure\Data;

use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Component\Component;

class Structure extends Component
{
    public ?int $id = null;

    public ?int $maxLevels = null;

    public ?string $uid = null;

    #[\Override]
    public function getRules(): array
    {
        return [
            'id' => ['nullable', 'integer'],
            'uid' => ['nullable', 'uuid'],
            'maxLevels' => ['nullable', 'integer'],
        ];
    }

    public function isSortable(): bool
    {
        return SessionAuth::checkAuthorization("editStructure:{$this->id}");
    }
}
