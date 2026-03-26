<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Contracts;

use CraftCms\Cms\View\Data\TemplateCacheContext;

interface CacheCollectorInterface
{
    public static function key(): string;

    public function begin(TemplateCacheContext $context): void;

    public function end(TemplateCacheContext $context): mixed;

    public function apply(mixed $payload, TemplateCacheContext $context): void;
}
