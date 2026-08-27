<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\Activities;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\EventTypes\ElementCreated;
use CraftCms\Cms\Activity\EventTypes\ElementUpdated;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\GarbageCollection\Actions\PurgeExpiredActivity;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

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

it('purges eligible standalone events and complete comment groups', function () {
    Cms::config()->activityRetentionDuration(3600);
    $activities = app(Activities::class);
    $author = User::factory()->createElement();
    $entry = Entry::factory()->createElement();
    $site = Sites::getSiteById(Site::factory()->create()->id);

    Date::setTestNow('2026-08-26 10:00:00');
    $expired = $activities->record(new ElementCreated(subject: $entry));
    $comment = $activities->createComment($entry, $author, $site, 'Original comment');

    Date::setTestNow('2026-08-26 12:00:00');
    $edit = $activities->editComment($comment, $author, 'Edited comment', $entry);
    $retained = $activities->record(new ElementUpdated(subject: $entry));
    DB::table(Table::ACTIVITYNOTIFICATIONS)->insert([
        'activityEventId' => $comment->id,
        'userId' => $author->id,
        'versionEventId' => $edit->id,
    ]);

    app(PurgeExpiredActivity::class)();

    expect(ActivityEvent::query()->pluck('id')->all())->toBe([$retained->id])
        ->and(DB::table(Table::ACTIVITYNOTIFICATIONS)->count())->toBe(0)
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
