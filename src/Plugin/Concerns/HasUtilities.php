<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Utility\Events\RegisterUtilities;
use CraftCms\Cms\Utility\Utility;
use Illuminate\Support\Facades\Event;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasUtilities
{
    /**
     * Array of utility classes to register.
     *
     * @var class-string<Utility>[]
     */
    protected array $utilities = [];

    public function bootHasUtilities(): void
    {
        if (! $this->utilities) {
            return;
        }

        Event::listen(RegisterUtilities::class, function (RegisterUtilities $event) {
            $event->types->push(...$this->utilities);
        });
    }
}
