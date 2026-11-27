<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Events;

/**
 * @since 6.0.0
 */
class CpAlertsResolving
{
    public function __construct(
        /** @var array<int, string|array{content: string, showIcon: bool, title?: string, actions?: list<array{label: string, url: string}>, refreshable?: bool}> */
        public array $alerts = [],
    ) {}
}
