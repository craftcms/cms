<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue\Data;

use Carbon\Carbon;
use CraftCms\Cms\Queue\Enums\JobStatus;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Dto;

use function CraftCms\Cms\t;

final class ProgressData extends Dto
{
    public string $uid;

    public string $description {
        get => t($this->description);
        set(?string $value) => $this->description = (string) $value;
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

    #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s', timeZone: 'UTC')]
    public ?Carbon $dateCreated = null;

    public ?int $delay = null;
}
