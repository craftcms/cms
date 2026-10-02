<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use Closure;
use CraftCms\Cms\FieldLayout\NativeFields;
use CraftCms\Cms\Plugin\Plugin;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasNativeFields
{
    /**
     * Returns a provider that modifies the native fields available to field layouts.
     */
    protected function getNativeFields(): ?Closure
    {
        return null;
    }

    public function bootHasNativeFields(): void
    {
        $provider = $this->getNativeFields();

        if ($provider === null) {
            return;
        }

        $this->app->make(NativeFields::class)->register($this->handle, $provider);
    }
}
