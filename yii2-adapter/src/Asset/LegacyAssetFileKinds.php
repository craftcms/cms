<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Asset;

use craft\base\Event as YiiEvent;
use craft\events\RegisterAssetFileKindsEvent;
use craft\helpers\Assets;
use CraftCms\Cms\Asset\AssetFileKinds;
use Override;

/** @internal */
class LegacyAssetFileKinds extends AssetFileKinds
{
    #[Override]
    public function fileKinds(): array
    {
        $fileKinds = parent::fileKinds();

        if (!YiiEvent::hasHandlers(Assets::class, Assets::EVENT_REGISTER_FILE_KINDS)) {
            return $fileKinds;
        }

        $event = new RegisterAssetFileKindsEvent(['fileKinds' => $fileKinds]);
        YiiEvent::trigger(Assets::class, Assets::EVENT_REGISTER_FILE_KINDS, $event);

        return $event->fileKinds;
    }
}
