<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Plugin\Plugin;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait InteractsWithCp
{
    /** @var bool Whether the plugin has its own section in the control panel */
    public bool $hasCpSection = false;

    public function getCpNavItem(): ?array
    {
        $ret = [
            'label' => $this->name,
            'url' => $this->handle,
        ];

        if (($iconPath = $this->cpNavIconPath()) !== null) {
            $ret['icon'] = $iconPath;
        }

        return $ret;
    }

    /**
     * Returns the path to the SVG icon that should be used in the plugin’s nav item in the control panel.
     *
     * @see getCpNavItem()
     */
    protected function cpNavIconPath(): ?string
    {
        $path = $this->getBasePath().'/icon-mask.svg';

        return is_file($path) ? $path : null;
    }
}
