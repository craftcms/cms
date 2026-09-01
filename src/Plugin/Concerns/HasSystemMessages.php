<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use Closure;
use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\SystemMessage\SystemMessages;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasSystemMessages
{
    /** @return array<string, Closure> */
    protected function getSystemMessages(): array
    {
        return [];
    }

    public function bootHasSystemMessages(): void
    {
        $systemMessages = $this->app->make(SystemMessages::class);

        foreach ($this->getSystemMessages() as $key => $factory) {
            $systemMessages->register($key, $factory);
        }
    }
}
