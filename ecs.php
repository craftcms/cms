<?php

declare(strict_types=1);

use craft\ecs\SetList;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function(ECSConfig $ecsConfig): void {
    $ecsConfig->parallel();
    $ecsConfig->paths([
        __DIR__ . '/bootstrap',
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __FILE__,
    ]);
    $ecsConfig->sets([SetList::CRAFT_CMS_4]);
};
