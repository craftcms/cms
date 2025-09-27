<?php

use craft\ecs\SetList;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function(ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__ . '/yii2-adapter/bootstrap',
        __DIR__ . '/yii2-adapter/legacy',
        __DIR__ . '/yii2-adapter/legacy-tests',
        __DIR__ . '/scripts',
        __FILE__,
    ]);
    $ecsConfig->skip([
        __DIR__ . '/yii2-adapter/legacy-tests/unit/helpers/typecast',
    ]);

    $ecsConfig->parallel();
    $ecsConfig->sets([SetList::CRAFT_CMS_4]);
};
