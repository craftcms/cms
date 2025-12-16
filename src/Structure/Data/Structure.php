<?php

declare(strict_types=1);

namespace CraftCms\Cms\Structure\Data;

use Craft;
use Spatie\LaravelData\Dto;

final class Structure extends Dto
{
    public function __construct(
        public ?int $id = null,
        public ?int $maxLevels = null,
        public ?string $uid = null,
    ) {}

    public function isSortable(): bool
    {
        return Craft::$app->getSession()->checkAuthorization("editStructure:{$this->id}");
    }
}
