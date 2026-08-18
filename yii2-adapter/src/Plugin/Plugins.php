<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Plugin;

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Plugin\Plugins as CorePlugins;
use Override;

class Plugins extends CorePlugins
{
    #[Override]
    public function createPlugin(string $handle, ?array $info = null): ?PluginInterface
    {
        foreach ($this->getComposerPluginInfo($handle)['aliases'] ?? [] as $alias => $path) {
            Aliases::set($alias, $path);
        }

        return parent::createPlugin($handle, $info);
    }
}
