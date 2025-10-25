<?php

use craft\ecs\SetList;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function(ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__ . '/bootstrap',
        __DIR__ . '/legacy',
        __DIR__ . '/tests',
        __FILE__,
    ]);
    $ecsConfig->skip([
        __DIR__ . '/tests/unit/helpers/typecast',
        __DIR__ . '/legacy/services/Gc.php',
        __DIR__ . '/legacy/base/ApplicationTrait.php',
    ]);

    $ecsConfig->parallel();
    $ecsConfig->sets([SetList::CRAFT_CMS_4]);
};
