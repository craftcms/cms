<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Models;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Filesystem\Contracts\UploadHandler;
use CraftCms\Cms\Shared\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property class-string<UploadHandler> $handler
 * @property string $owner
 * @property string $uploader
 * @property string $disk
 * @property string $filename
 * @property int $size
 * @property int $chunkSize
 * @property array<string, mixed> $parameters
 * @property array<string, mixed> $state
 * @property array<string, mixed>|null $result
 * @property Carbon $expiresAt
 */
class UploadSession extends BaseModel
{
    #[\Override]
    public $incrementing = false;

    #[\Override]
    protected $keyType = 'string';

    #[\Override]
    protected $table = Table::UPLOADSESSIONS;

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'chunkSize' => 'integer',
            'parameters' => 'array',
            'state' => 'array',
            'result' => 'array',
            'expiresAt' => 'datetime',
        ];
    }

    public function prefix(): string
    {
        return "upload-sessions/{$this->id}";
    }

    public function path(): string
    {
        return $this->prefix().'/file';
    }

    public function partCount(): int
    {
        return max(1, (int) ceil($this->size / $this->chunkSize));
    }

    public function partSize(int $part): int
    {
        abort_unless($part >= 1 && $part <= $this->partCount(), 422, 'Invalid upload part.');

        return min($this->chunkSize, $this->size - ($part - 1) * $this->chunkSize);
    }
}
