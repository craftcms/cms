<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue\Data;

use CraftCms\Cms\Queue\Enums\JobStatus;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Dto;

use function CraftCms\Cms\t;

final class ProgressData extends Dto
{
    public string $uid;

    public string $description {
        get => t($this->description);
        set => $this->description = $value;
    }

    public JobStatus $status;

    /** @var int<0, 100> */
    public int $progress;

    #[MapInputName('progressLabel')]
    public ?string $label {
        get => $this->label ? t($this->label) : null;
        set => $this->label = $value;
    }

    public ?string $error = null;
}
