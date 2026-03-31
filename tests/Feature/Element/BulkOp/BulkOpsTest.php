<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\BulkOp\BulkOps;
use CraftCms\Cms\Element\BulkOp\Events\AfterBulkOp;
use CraftCms\Cms\Element\BulkOp\Events\BeforeBulkOp;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Facade;

beforeEach(function () {
    Craft::$app->controller = null;
    $this->bulkOps = app(BulkOps::class);
    $this->bulkOpConnection = DB::connection('db2');
});

it('is scoped within a request', function () {
    expect(app(BulkOps::class))->toBe(app(BulkOps::class));
});

it('starts a bulk op and dispatches before event', function () {
    Event::fake([BeforeBulkOp::class]);

    $key = $this->bulkOps->start();

    expect($key)->toHaveLength(10)
        ->and($this->bulkOps->activeKeys())->toBe([$key]);

    Event::assertDispatched(fn (BeforeBulkOp $event): bool => $event->key === $key);
});

it('can resume a supplied bulk op key', function () {
    $this->bulkOps->resume('existing-key');

    expect($this->bulkOps->activeKeys())->toBe(['existing-key']);
});

it('tracks elements in active bulk ops', function () {
    $entry = Entry::factory()->create();

    Entry::factory()->create();

    $key = $this->bulkOps->start();

    $this->bulkOps->trackElement(EntryElement::findOne($entry->id));

    expect($this->bulkOpConnection->table(Table::ELEMENTS_BULKOPS)
        ->where('key', $key)
        ->count())->toBe(1)
        ->and($this->bulkOpConnection->table(Table::ELEMENTS_BULKOPS)
            ->where('key', $key)
            ->value('elementId'))->toBe($entry->id);
});

it('upserts tracked elements for the same key', function () {
    $entry = Entry::factory()->create();

    $key = $this->bulkOps->start();
    $element = EntryElement::findOne($entry->id);

    $this->bulkOps->trackElement($element);
    $this->bulkOps->trackElement($element);

    expect($this->bulkOpConnection->table(Table::ELEMENTS_BULKOPS)
        ->where('key', $key)
        ->count())->toBe(1);
});

it('tracks an element for every active bulk op key', function () {
    $entry = Entry::factory()->create();

    $firstKey = $this->bulkOps->start();
    $secondKey = $this->bulkOps->start();

    $this->bulkOps->trackElement(EntryElement::findOne($entry->id));

    expect($this->bulkOpConnection->table(Table::ELEMENTS_BULKOPS)
        ->where('elementId', $entry->id)
        ->whereIn('key', [$firstKey, $secondKey])
        ->count())->toBe(2);
});

it('dispatches after event before cleaning up persisted rows', function () {
    $entry = Entry::factory()->create();

    $key = $this->bulkOps->start();
    $this->bulkOps->trackElement(EntryElement::findOne($entry->id));

    $rowsVisibleDuringAfterEvent = null;

    Event::listen(AfterBulkOp::class, function (AfterBulkOp $event) use (&$rowsVisibleDuringAfterEvent, $key) {
        if ($event->key !== $key) {
            return;
        }

        $rowsVisibleDuringAfterEvent = Craft::$app->getElements()->getBulkOpConnection()
            ->table(Table::ELEMENTS_BULKOPS)
            ->where('key', $event->key)
            ->count();
    });

    $this->bulkOps->end($key);

    expect($rowsVisibleDuringAfterEvent)->toBe(1)
        ->and($this->bulkOps->activeKeys())->toBe([])
        ->and($this->bulkOpConnection->table(Table::ELEMENTS_BULKOPS)->where('key', $key)->count())->toBe(0);
});

it('ensures a bulk op around a callback when none is active', function () {
    Event::fake([
        BeforeBulkOp::class,
        AfterBulkOp::class,
    ]);

    $key = $this->bulkOps->ensure(fn (): string => $this->bulkOps->activeKeys()[0]);

    expect($key)->toHaveLength(10)
        ->and($this->bulkOps->activeKeys())->toBe([]);

    Event::assertDispatched(fn (BeforeBulkOp $event): bool => $event->key === $key);
    Event::assertDispatched(fn (AfterBulkOp $event): bool => $event->key === $key);
});

it('reuses the outer bulk op for nested ensure calls', function () {
    $startedKeys = [];
    $endedKeys = [];

    Event::listen(BeforeBulkOp::class, function (BeforeBulkOp $event) use (&$startedKeys) {
        $startedKeys[] = $event->key;
    });

    Event::listen(AfterBulkOp::class, function (AfterBulkOp $event) use (&$endedKeys) {
        $endedKeys[] = $event->key;
    });

    $innerKey = null;

    $outerKey = $this->bulkOps->ensure(function (): string {
        $outerKey = $this->bulkOps->activeKeys()[0];

        $innerKey = $this->bulkOps->ensure(function () use ($outerKey): string {
            expect($this->bulkOps->activeKeys())->toBe([$outerKey]);

            return $this->bulkOps->activeKeys()[0];
        });

        expect($innerKey)->toBe($outerKey)
            ->and($this->bulkOps->activeKeys())->toBe([$outerKey]);

        return $outerKey;
    });

    expect($startedKeys)->toBe([$outerKey])
        ->and($endedKeys)->toBe([$outerKey])
        ->and($this->bulkOps->activeKeys())->toBe([]);
});

it('cleans up the active key when ensure throws', function () {
    $endedKeys = [];

    Event::listen(AfterBulkOp::class, function (AfterBulkOp $event) use (&$endedKeys) {
        $endedKeys[] = $event->key;
    });

    try {
        $this->bulkOps->ensure(function () {
            throw new RuntimeException('bulk op failed');
        });

        $this->fail('Expected ensure() to rethrow the callback exception.');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('bulk op failed');
    }

    expect($endedKeys)->toHaveCount(1)
        ->and($this->bulkOps->activeKeys())->toBe([]);
});

it('skips persistence during updater requests', function () {
    $request = Request::create('/news?action=app/update', 'POST');

    app()->instance('request', $request);
    Facade::clearResolvedInstance('request');

    $runningInConsole = new ReflectionProperty(app(), 'isRunningInConsole');
    $originalRunningInConsole = $runningInConsole->getValue(app());
    $runningInConsole->setValue(app(), false);

    try {
        $entry = Entry::factory()->create();

        $key = $this->bulkOps->start();
        $this->bulkOps->trackElement(EntryElement::findOne($entry->id));

        expect($this->bulkOpConnection->table(Table::ELEMENTS_BULKOPS)
            ->where('key', $key)
            ->count())->toBe(0);

        $this->bulkOps->end($key);

        expect($this->bulkOpConnection->table(Table::ELEMENTS_BULKOPS)
            ->where('key', $key)
            ->count())->toBe(0);
    } finally {
        $runningInConsole->setValue(app(), $originalRunningInConsole);
    }
});
