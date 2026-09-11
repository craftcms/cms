<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

use Illuminate\Contracts\Support\Arrayable;
use Spatie\TypeScriptTransformer\Attributes\Optional;

/** @implements Arrayable<string, mixed> */
#[Optional]
class UploadResult implements Arrayable
{
    public private(set) ?int $assetId;

    public private(set) string $filename;

    public private(set) ?string $url;

    public private(set) string $conflict;

    public private(set) ?int $conflictingAssetId;

    public private(set) ?string $suggestedFilename;

    public private(set) ?string $conflictingAssetUrl;

    public private(set) string $formattedSize;

    public private(set) string $formattedSizeInBytes;

    public private(set) string $formattedDateUpdated;

    public private(set) ?string $dimensions;

    public private(set) int $updatedTimestamp;

    public private(set) ?string $resultingUrl;

    public private(set) string $message;

    public private(set) string $modelName;

    /** @var class-string */
    public private(set) string $modelClass;

    /** @var array<string, mixed> */
    public private(set) array $model;

    /** @var array<string, list<string>> */
    public private(set) array $errors;

    /** @param array<string, mixed> $data */
    public function __construct(array $data, public private(set) int $status = 200)
    {
        foreach ($data as $name => $value) {
            $this->{$name} = $value;
        }
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        $data = get_object_vars($this);
        unset($data['status']);

        return $data;
    }

    public function toArray(): array
    {
        return ['data' => $this->payload(), 'status' => $this->status];
    }
}
