<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

use CraftCms\Cms\Component\Component;
use Override;

final class FolderCriteria extends Component
{
    public mixed $id = null;

    public mixed $parentId = null;

    public mixed $volumeId = null;

    public mixed $name = null;

    public mixed $path = null;

    public string|array $order = 'name asc';

    public ?int $offset = null;

    public ?int $limit = null;

    public mixed $uid = null;

    public mixed $sourceId = null;

    #[Override]
    public function getRules(): array
    {
        return [
            'id' => ['nullable', 'integer'],
            'parentId' => ['nullable', 'integer'],
            'sourceId' => ['nullable', 'integer'],
            'offset' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer'],
        ];
    }
}
