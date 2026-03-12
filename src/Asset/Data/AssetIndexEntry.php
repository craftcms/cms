<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

use CraftCms\Cms\Component\Component;
use DateTime;
use Stringable;

final class AssetIndexEntry extends Component implements Stringable
{
    public ?int $id = null;

    public ?int $volumeId = null;

    public ?int $sessionId = null;

    public ?string $uri = null;

    public ?int $size = null;

    public ?int $recordId = null;

    public ?bool $isSkipped = null;

    public ?DateTime $timestamp = null;

    public bool $isDir = false;

    public bool $completed = false;

    public bool $inProgress = false;

    public function __toString(): string
    {
        return (string) $this->uri;
    }
}
