<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

use CraftCms\Cms\Component\Component;
use DateTime;
use Illuminate\Support\Traits\Macroable;

final class IndexingSession extends Component
{
    use Macroable;

    public ?string $uid = null;

    public ?int $id = null;

    public string $indexedVolumes = '';

    public ?int $totalEntries = null;

    public int $processedEntries = 0;

    public bool $cacheRemoteImages = false;

    public bool $listEmptyFolders = false;

    public bool $isCli = false;

    public bool $actionRequired = false;

    public bool $processIfRootEmpty = false;

    public ?DateTime $dateCreated = null;

    public ?DateTime $dateUpdated = null;

    /** @var string[] */
    public array $skippedEntries = [];

    /** @var array<string, mixed> */
    public array $missingEntries = [];

    public bool $forceStop = false;
}
