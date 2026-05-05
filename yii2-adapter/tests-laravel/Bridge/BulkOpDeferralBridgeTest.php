<?php

declare(strict_types=1);

use craft\events\BulkOpEvent;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\BulkOp\BulkOpDeferrals;
use CraftCms\Cms\Element\BulkOp\Events\DeferredBulkOpReplayed;
use CraftCms\Cms\Tests\TestCase as CmsTestCase;
use Illuminate\Support\Facades\DB;

uses(CmsTestCase::class);

class AdapterDeferredBulkEvent
{
    public function __construct(
        public string $value,
    ) {
    }
}

beforeEach(function() {
    $this->deferrals = app(BulkOpDeferrals::class);
    $this->bulkOpConnection = DB::connection('db2');
});

it('replays native deferred handlers when a legacy bulk op ends', function() {
    $replays = [];

    $this->deferrals->defer(AdapterDeferredBulkEvent::class, function(DeferredBulkOpReplayed $event) use (&$replays) {
        $replays[] = $event;
    }, data: ['source' => 'legacy']);

    $key = Craft::$app->getElements()->beginBulkOp();

    event(new AdapterDeferredBulkEvent('inside'));

    expect($this->bulkOpConnection->table(Table::BULKOPEVENTS)->count())->toBe(0);

    Craft::$app->getElements()->endBulkOp($key);

    expect($replays)->toHaveCount(1)
        ->and($replays[0]->key)->toBe($key)
        ->and($replays[0]->data)->toBe(['source' => 'legacy']);
});

it('persists native deferred triggers while a legacy bulk op remains open', function() {
    $this->deferrals->defer(AdapterDeferredBulkEvent::class, function() {
    });

    $key = Craft::$app->getElements()->beginBulkOp();

    event(new AdapterDeferredBulkEvent('inside'));
    $this->deferrals->persistPending();

    expect($this->bulkOpConnection->table(Table::BULKOPEVENTS)
        ->where('key', $key)
        ->count())->toBe(1);
});

it('replays legacy deferred handlers keyed by event name', function() {
    $replays = [];

    BulkOpEvent::defer(AdapterDeferredBulkEvent::class, 'custom-event', function(BulkOpEvent $event) use (&$replays) {
        $replays[] = [
            'key' => $event->key,
            'data' => $event->data,
        ];
    }, data: ['source' => 'custom-event']);

    BulkOpEvent::defer(AdapterDeferredBulkEvent::class, 'other-custom-event', function(BulkOpEvent $event) use (&$replays) {
        $replays[] = [
            'key' => $event->key,
            'data' => $event->data,
        ];
    }, data: ['source' => 'other-custom-event']);

    $key = Craft::$app->getElements()->beginBulkOp();

    event(new AdapterDeferredBulkEvent('inside'));

    Craft::$app->getElements()->endBulkOp($key);

    expect($replays)->toHaveCount(2)
        ->and($replays)->toContain([
            'key' => $key,
            'data' => ['source' => 'custom-event'],
        ])
        ->and($replays)->toContain([
            'key' => $key,
            'data' => ['source' => 'other-custom-event'],
        ]);
});
