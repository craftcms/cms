<?php

declare(strict_types=1);

namespace craft\models;

use CraftCms\Cms\Asset\Enums\AssetIndexStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Models\AssetIndexData} instead.
 */
class AssetIndexData extends \CraftCms\Cms\Asset\Models\AssetIndexData
{
    public function getInProgress(): bool
    {
        return $this->status === AssetIndexStatus::Processing;
    }

    public function getCompleted(): bool
    {
        return in_array($this->status, [
            AssetIndexStatus::Indexed,
            AssetIndexStatus::Skipped,
            AssetIndexStatus::Missing,
            AssetIndexStatus::Failed,
        ], true);
    }

    public function getIsSkipped(): bool
    {
        return in_array($this->status, [
            AssetIndexStatus::Skipped,
            AssetIndexStatus::Missing,
            AssetIndexStatus::Failed,
        ], true);
    }

    protected function inProgress(): Attribute
    {
        return Attribute::get(fn(): bool => $this->getInProgress());
    }

    protected function completed(): Attribute
    {
        return Attribute::get(fn(): bool => $this->getCompleted());
    }

    protected function isSkipped(): Attribute
    {
        return Attribute::get(fn(): bool => $this->getIsSkipped());
    }
}
