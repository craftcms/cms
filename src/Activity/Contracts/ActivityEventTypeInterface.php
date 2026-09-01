<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\Contracts;

use CraftCms\Cms\Activity\Data\ActivityActor;
use CraftCms\Cms\Activity\Data\ActivityChange;
use CraftCms\Cms\Activity\Data\ActivitySource;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Site\Data\Site;
use Illuminate\Contracts\Support\Htmlable;

interface ActivityEventTypeInterface
{
    public function subject(): ?ActivitySubject;

    public function actor(): ?ActivityActor;

    public function site(): ?Site;

    /** @return array<string, mixed> */
    public function data(): array;

    /** @return list<ActivityChange> */
    public function changes(): array;

    public static function source(): ActivitySource;

    public static function label(): string;

    public static function icon(): string;

    public static function format(ActivityEvent $event): string|Htmlable|null;
}
