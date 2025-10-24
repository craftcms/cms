<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Field extends BaseModel
{
    use HasFactory;
    use HasUid;
    use SoftDeletes;

    protected $table = Table::FIELDS;

    private ?string $oldHandle = null;

    protected function casts(): array
    {
        return [
            'settings' => 'json',
            'searchable' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        self::retrieved(function (self $field) {
            $field->storeOldData();
        });
    }

    public function storeOldData(): void
    {
        $this->oldHandle = $this->handle;
    }

    public function getOldHandle(): ?string
    {
        return $this->oldHandle;
    }
}
