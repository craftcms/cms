<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Event;

use craft\base\Event as YiiEvent;
use craft\events\BulkOpEvent;
use craft\services\Elements;
use CraftCms\Cms\Element\BulkOp\BulkOpDeferrals;

readonly class BulkOpDeferralBridge
{
    public function boot(): void
    {
        YiiEvent::on(Elements::class, Elements::EVENT_AFTER_BULK_OP, function(BulkOpEvent $event) {
            app(BulkOpDeferrals::class)->replay($event->key);
        }, append: false);
    }
}
