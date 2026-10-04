<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Enums;

enum AssetIngestStatus: string
{
    case Saved = 'saved';
    case Invalid = 'invalid';
    case Rejected = 'rejected';
}
