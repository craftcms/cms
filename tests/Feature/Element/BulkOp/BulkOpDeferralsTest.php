<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\BulkOp\BulkOpDeferrals;
use CraftCms\Cms\Element\BulkOp\BulkOps;
use CraftCms\Cms\Element\BulkOp\Events\DeferredBulkOpReplay;
use CraftCms\Cms\Entry\Elements\Entry;
use Illuminate\Support\Facades\DB;

class TestDeferredBulkEvent
{
    public function __construct(
        public string $value,
    ) {}
}

beforeEach(function () {
    $this->bulkOps = app(BulkOps::class);
    $this->deferrals = app(BulkOpDeferrals::class);
    $this->bulkOpConnection = DB::connection('db2');
});

it('does nothing outside a bulk op', function () {
    $calls = 0;

    $this->deferrals->defer(TestDeferredBulkEvent::class, function () use (&$calls) {
        $calls++;
    });

    event(new TestDeferredBulkEvent('outside'));
    $this->deferrals->persistPending();

    expect($calls)->toBe(0)
        ->and($this->bulkOpConnection->table(Table::BULKOPEVENTS)->count())->toBe(0);
});

it('replays a watched event when the bulk op ends', function () {
    $replays = [];

    $this->deferrals->defer(TestDeferredBulkEvent::class, function (DeferredBulkOpReplay $event) use (&$replays) {
        $replays[] = $event;
    }, data: ['source' => 'native']);

    $key = $this->bulkOps->start();

    event(new TestDeferredBulkEvent('inside'));
    $this->bulkOps->end($key);

    expect($replays)->toHaveCount(1)
        ->and($replays[0]->key)->toBe($key)
        ->and($replays[0]->event)->toBe(TestDeferredBulkEvent::class)
        ->and($replays[0]->watchKey)->toBe(TestDeferredBulkEvent::class)
        ->and($replays[0]->data)->toBe(['source' => 'native']);
});

it('collapses repeated watched events into one replay', function () {
    $calls = 0;

    $this->deferrals->defer(TestDeferredBulkEvent::class, function () use (&$calls) {
        $calls++;
    });

    $key = $this->bulkOps->start();

    event(new TestDeferredBulkEvent('first'));
    event(new TestDeferredBulkEvent('second'));
    event(new TestDeferredBulkEvent('third'));

    $this->bulkOps->end($key);

    expect($calls)->toBe(1);
});

it('replays multiple handlers for the same watched event', function () {
    $calls = [];

    $this->deferrals->defer(TestDeferredBulkEvent::class, function () use (&$calls) {
        $calls[] = 'first';
    });

    $this->deferrals->defer(TestDeferredBulkEvent::class, function () use (&$calls) {
        $calls[] = 'second';
    });

    $key = $this->bulkOps->start();

    event(new TestDeferredBulkEvent('inside'));
    $this->bulkOps->end($key);

    expect($calls)->toBe(['first', 'second']);
});

it('records and replays every watch key registered for the same event', function () {
    $replays = [];

    $this->deferrals->defer(TestDeferredBulkEvent::class, function (DeferredBulkOpReplay $event) use (&$replays) {
        $replays[] = $event->watchKey;
    }, watchKey: 'first-watch-key');

    $this->deferrals->defer(TestDeferredBulkEvent::class, function (DeferredBulkOpReplay $event) use (&$replays) {
        $replays[] = $event->watchKey;
    }, watchKey: 'second-watch-key');

    $key = $this->bulkOps->start();

    event(new TestDeferredBulkEvent('inside'));
    $this->bulkOps->end($key);

    expect($replays)->toBe(['first-watch-key', 'second-watch-key']);
});

it('replays once per active bulk op key and leaves other keys pending', function () {
    $replayedKeys = [];

    $this->deferrals->defer(TestDeferredBulkEvent::class, function (DeferredBulkOpReplay $event) use (&$replayedKeys) {
        $replayedKeys[] = $event->key;
    });

    $firstKey = $this->bulkOps->start();
    $secondKey = $this->bulkOps->start();

    event(new TestDeferredBulkEvent('inside'));

    $this->bulkOps->end($firstKey);

    expect($replayedKeys)->toBe([$firstKey]);

    $this->bulkOps->end($secondKey);

    expect($replayedKeys)->toBe([$firstKey, $secondKey]);
});

it('persists pending triggers for later replay', function () {
    $this->deferrals->defer(TestDeferredBulkEvent::class, function () {});

    $key = $this->bulkOps->start();

    event(new TestDeferredBulkEvent('inside'));
    $this->deferrals->persistPending();

    expect($this->bulkOpConnection->table(Table::BULKOPEVENTS)
        ->where('key', $key)
        ->count())->toBe(1);
});

it('replays persisted rows and deletes them', function () {
    $replays = [];

    $this->deferrals->defer(TestDeferredBulkEvent::class, function (DeferredBulkOpReplay $event) use (&$replays) {
        $replays[] = $event;
    }, data: ['source' => 'persisted']);

    $this->bulkOpConnection->table(Table::BULKOPEVENTS)->insert([
        'key' => 'persisted1',
        'senderClass' => TestDeferredBulkEvent::class,
        'eventName' => TestDeferredBulkEvent::class,
        'timestamp' => now(),
    ]);

    $this->deferrals->replay('persisted1');

    expect($replays)->toHaveCount(1)
        ->and($replays[0]->key)->toBe('persisted1')
        ->and($replays[0]->data)->toBe(['source' => 'persisted'])
        ->and($this->bulkOpConnection->table(Table::BULKOPEVENTS)->where('key', 'persisted1')->count())->toBe(0);
});

it('replays before elements bulk op rows are cleaned up', function () {
    $rowsVisibleDuringReplay = null;

    $this->deferrals->defer(TestDeferredBulkEvent::class, function (DeferredBulkOpReplay $event) use (&$rowsVisibleDuringReplay) {
        $rowsVisibleDuringReplay = DB::connection('db2')
            ->table(Table::ELEMENTS_BULKOPS)
            ->where('key', $event->key)
            ->count();
    });

    $entry = CraftCms\Cms\Entry\Models\Entry::factory()->create();
    $key = $this->bulkOps->start();

    $this->bulkOps->trackElement(Entry::findOne($entry->id));
    event(new TestDeferredBulkEvent('inside'));
    $this->bulkOps->end($key);

    expect($rowsVisibleDuringReplay)->toBe(1);
});

it('ignores persisted rows without a registered handler', function () {
    $this->bulkOpConnection->table(Table::BULKOPEVENTS)->insert([
        'key' => 'orphaned1',
        'senderClass' => TestDeferredBulkEvent::class,
        'eventName' => TestDeferredBulkEvent::class,
        'timestamp' => now(),
    ]);

    $this->deferrals->replay('orphaned1');

    expect($this->bulkOpConnection->table(Table::BULKOPEVENTS)->where('key', 'orphaned1')->count())->toBe(0);
});
