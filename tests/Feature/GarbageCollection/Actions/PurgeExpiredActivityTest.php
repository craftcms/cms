<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\Activities;
use CraftCms\Cms\Activity\ActivityComments;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\EventTypes\ElementCreated;
use CraftCms\Cms\Activity\EventTypes\ElementUpdated;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\GarbageCollection\Actions\PurgeExpiredActivity;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Models\User;
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

it('retains expired comment trees while purging ordinary expired activity', function () {
    Cms::config()->activityRetentionDuration(3600);
    $activities = app(Activities::class);
    $comments = app(ActivityComments::class);
    $author = User::factory()->createElement();
    $entry = Entry::factory()->createElement();
    $site = Sites::getSiteById(Site::factory()->create()->id);

    Date::setTestNow('2026-08-26 10:00:00');
    $expired = $activities->record(new ElementCreated(subject: $entry));
    $comment = $comments->create($entry, $author, $site, 'Original comment');

    Date::setTestNow('2026-08-26 10:15:00');
    $edit = $comments->edit($comment, $author, 'Edited comment', $entry);

    Date::setTestNow('2026-08-26 10:30:00');
    $deletion = $comments->delete($comment, $author);

    Date::setTestNow('2026-08-26 12:00:00');
    $retained = $activities->record(new ElementUpdated(subject: $entry));

    app(PurgeExpiredActivity::class)();

    expect(ActivityEvent::query()->orderBy('id')->pluck('id')->all())->toBe([
        $comment->id,
        $edit->id,
        $deletion->id,
        $retained->id,
    ])
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
