<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\Activities;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\EventTypes\ElementCreated;
use CraftCms\Cms\Activity\EventTypes\ElementUpdated;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Cms;
use CraftCms\Cms\GarbageCollection\Actions\PurgeExpiredActivity;
use Illuminate\Support\Facades\Date;

afterEach(fn () => Date::setTestNow());

it('leaves activity intact when retention is unlimited', function () {
    Date::setTestNow('2025-08-26 12:00:00');
    $event = app(Activities::class)->record(new ElementCreated(
        subject: new ActivitySubject('document', 'one', 'Document one'),
    ));

    Date::setTestNow('2026-08-26 12:00:00');
    app(PurgeExpiredActivity::class)();

    expect(ActivityEvent::query()->whereKey($event->id)->exists())->toBeTrue();
});

it('purges activity older than the retention duration', function () {
    Cms::config()->activityRetentionDuration(3600);
    $activities = app(Activities::class);
    $subject = new ActivitySubject('document', 'one', 'Document one');

    Date::setTestNow('2026-08-26 10:00:00');
    $expired = $activities->record(new ElementCreated(subject: $subject));

    Date::setTestNow('2026-08-26 12:00:00');
    $retained = $activities->record(new ElementUpdated(subject: $subject));

    app(PurgeExpiredActivity::class)();

    expect(ActivityEvent::query()->whereKey($retained->id)->exists())->toBeTrue()
        ->and(ActivityEvent::query()->whereKey($expired->id)->exists())->toBeFalse();
});

it('retains events until they cross the cutoff', function () {
    Cms::config()->activityRetentionDuration(3600);
    Date::setTestNow('2026-08-26 11:00:00');
    $event = app(Activities::class)->record(new ElementCreated(
        subject: new ActivitySubject('document', 'one', 'Document one'),
    ));

    Date::setTestNow('2026-08-26 12:00:00');
    app(PurgeExpiredActivity::class)();

    expect(ActivityEvent::query()->whereKey($event->id)->exists())->toBeTrue();
});
