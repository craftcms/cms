<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Console\SchedulerHeartbeat;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Models\Revision;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Release\Enums\ReleaseStatus;
use CraftCms\Cms\Release\Releases;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Route;

use function CraftCms\Cms\action_url;

beforeEach(function () {
    Date::setTestNow('2026-10-01 12:00:00');

    $this->actor = $actor = User::findOne();
    $releases = app(Releases::class);

    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->create(['enableVersioning' => true]);
    Sections::refreshSections();
    EntryTypes::refreshEntryTypes();

    $canonical = EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement(['title' => 'Original']);
    $draft = app(Drafts::class)->createDraft(canonical: $canonical, creatorId: $actor->id, name: 'Scheduled draft');
    $draft->title = 'Released';
    $draft->setAuthorIds([$actor->id]);
    expect(Elements::saveElement($draft))->toBeTrue();

    $release = $releases->create('Scheduled release', null, $actor);
    $releases->attach($release, $draft, $actor);
    $this->release = $releases->schedule($release, now()->addMinute(), $actor);
    $this->canonicalId = $canonical->id;

    Date::setTestNow('2026-10-01 12:05:00');

    $_SERVER['LARAVEL_OCTANE'] = 1;

    Route::middleware(['web', 'craft', 'craft.web'])
        ->get('scheduled-work-test-page', fn () => response('<html><body>Page</body></html>')->header('Content-Type', 'text/html'));
});

afterEach(function () {
    Date::setTestNow();
    unset($_SERVER['LARAVEL_OCTANE']);
});

it('publishes due scheduled releases after a site request when the scheduler is not running', function () {
    $this->get('/');

    expect($this->release->fresh()->status)->toBe(ReleaseStatus::Published);
});

it('does not record the site request’s user as the publisher', function () {
    $this->actingAs($this->actor)->get('/');

    expect($this->release->fresh()->status)->toBe(ReleaseStatus::Published)
        ->and(Revision::query()->where('canonicalId', $this->canonicalId)->latest('num')->value('creatorId'))->toBeNull();
});

it('checks for due work at most once a minute', function () {
    Date::setTestNow('2026-10-01 12:00:30');
    $this->get('/');

    Date::setTestNow('2026-10-01 12:01:10');
    $this->get('/');
    expect($this->release->fresh()->status)->toBe(ReleaseStatus::Scheduled);

    Date::setTestNow('2026-10-01 12:01:40');
    $this->get('/');
    expect($this->release->fresh()->status)->toBe(ReleaseStatus::Published);
});

it('leaves scheduled releases to the scheduler while it is running', function () {
    app(SchedulerHeartbeat::class)->record();

    $this->get('/');

    expect($this->release->fresh()->status)->toBe(ReleaseStatus::Scheduled);
});

it('takes over once the scheduler heartbeat is stale', function () {
    app(SchedulerHeartbeat::class)->record();
    Date::setTestNow(now()->addMinutes(4));

    $this->get('/');

    expect($this->release->fresh()->status)->toBe(ReleaseStatus::Published);
});

it('does not run scheduled work when runScheduledWorkAutomatically is disabled', function () {
    Cms::config()->runScheduledWorkAutomatically(false);

    $this->get('/');

    expect($this->release->fresh()->status)->toBe(ReleaseStatus::Scheduled);
});

it('requests due work in the background from HTML pages where the response cannot end early', function () {
    unset($_SERVER['LARAVEL_OCTANE']);

    $this->get('scheduled-work-test-page')->assertSee('scheduled-work\\/run', false);
    expect($this->release->fresh()->status)->toBe(ReleaseStatus::Scheduled);

    $this->get(action_url('scheduled-work/run'))->assertOk();
    expect($this->release->fresh()->status)->toBe(ReleaseStatus::Published);

    $this->get('scheduled-work-test-page')->assertDontSee('scheduled-work', false);
});

it('checks for due work from HTML pages at most once a minute', function () {
    unset($_SERVER['LARAVEL_OCTANE']);

    Date::setTestNow('2026-10-01 12:00:30');
    $this->get('scheduled-work-test-page')->assertDontSee('scheduled-work', false);

    Date::setTestNow('2026-10-01 12:01:10');
    $this->get('scheduled-work-test-page')->assertDontSee('scheduled-work', false);

    Date::setTestNow('2026-10-01 12:01:40');
    $this->get('scheduled-work-test-page')->assertSee('scheduled-work\\/run', false);
});

it('does not request due work in the background while the scheduler is running', function () {
    unset($_SERVER['LARAVEL_OCTANE']);
    app(SchedulerHeartbeat::class)->record();

    $this->get('scheduled-work-test-page')->assertDontSee('scheduled-work', false);
});

it('does not run scheduled work from the background action while the scheduler is running', function () {
    app(SchedulerHeartbeat::class)->record();

    $this->get(action_url('scheduled-work/run'))->assertOk();

    expect($this->release->fresh()->status)->toBe(ReleaseStatus::Scheduled);
});
