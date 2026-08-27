<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\Activities;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Cms;
use CraftCms\Cms\GarbageCollection\Actions\PurgeExpiredActivity;
use Illuminate\Support\Facades\Date;

afterEach(fn () => Date::setTestNow());

it('leaves activity intact when retention is unlimited', function () {
    Date::setTestNow('2025-08-26 12:00:00');
    $event = app(Activities::class)->record(
        'craft.element.created',
        subject: new ActivitySubject('document', 'one', 'Document one'),
    );

    Date::setTestNow('2026-08-26 12:00:00');
    app(PurgeExpiredActivity::class)();

    expect(ActivityEvent::query()->pluck('id')->all())->toBe([$event->id]);
});

it('purges activity older than the retention duration', function () {
    Cms::config()->activityRetentionDuration(3600);
    $activities = app(Activities::class);
    $subject = new ActivitySubject('document', 'one', 'Document one');

    Date::setTestNow('2026-08-26 10:00:00');
    $expired = $activities->record('craft.element.created', subject: $subject);

    Date::setTestNow('2026-08-26 12:00:00');
    $retained = $activities->record('craft.element.updated', subject: $subject);

    app(PurgeExpiredActivity::class)();

    expect(ActivityEvent::query()->pluck('id')->all())->toBe([$retained->id])
        ->and(ActivityEvent::query()->find($expired->id))->toBeNull();
});

it('retains events until they cross the cutoff', function () {
    Cms::config()->activityRetentionDuration(3600);
    Date::setTestNow('2026-08-26 11:00:00');
    $event = app(Activities::class)->record(
        'craft.element.created',
        subject: new ActivitySubject('document', 'one', 'Document one'),
    );

    Date::setTestNow('2026-08-26 12:00:00');
    app(PurgeExpiredActivity::class)();

    expect(ActivityEvent::query()->pluck('id')->all())->toBe([$event->id]);
});
