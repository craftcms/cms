<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

final class Asset extends BaseModel
{
    use HasFactory;

    protected $table = Table::ASSETS;

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'width' => 'int',
            'height' => 'int',
            'size' => 'int',
            'deletedWithVolume' => 'bool',
            'keptFile' => 'bool',
            'dateModified' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Element, $this>
     */
    public function element(): BelongsTo
    {
        return $this->belongsTo(Element::class, 'id');
    }

    /**
     * @return BelongsToMany<Site, $this, Pivot>
     */
    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class, 'assets_sites', 'assetId', 'siteId')
            ->withPivot('alt');
    }

    /**
     * @return BelongsTo<Volume, $this>
     */
    public function volume(): BelongsTo
    {
        return $this->belongsTo(Volume::class, 'volumeId');
    }

    /**
     * @return BelongsTo<VolumeFolder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(VolumeFolder::class, 'folderId');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaderId');
    }
}
