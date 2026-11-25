<?php

declare(strict_types=1);

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Console\Application as ConsoleApplication;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Commands\UpdateElementStatusesCommand;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementStatuses;
use CraftCms\Cms\Element\Events\ElementCachesInvalidated;
use CraftCms\Cms\Element\Events\ElementSaved;
use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\Element\Events\ElementStatusChanged;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Commands\UpdateStatusesCommand;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Updates;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Console\Scheduling\Event as ScheduledEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function () {
    Date::setTestNow('2026-10-01 12:00:00');

    $this->changes = [];
    Event::listen(ElementStatusChanged::class, function (ElementStatusChanged $event) {
        $this->changes[] = [$event->element->id, $event->oldStatus, $event->newStatus];
    });

    $this->scheduledEntry = function (string $postDate, bool $enabled = true): Entry {
        $entry = EntryModel::factory()->createElement();
        Sections::refreshSections();
        EntryTypes::refreshEntryTypes();
        $entry->postDate = Date::parse($postDate);
        $entry->enabled = $enabled;
        expect(Elements::saveElement($entry))->toBeTrue();
        $this->changes = [];

        return $entry;
    };
});

afterEach(function () {
    Date::setTestNow();
});

it('announces each entry going live once, from the first run in its post date’s minute', function () {
    $entry = ($this->scheduledEntry)('2026-10-01 12:05:30');
    $invalidatedIds = [];
    Event::listen(ElementCachesInvalidated::class, function (ElementCachesInvalidated $event) use (&$invalidatedIds) {
        $invalidatedIds[] = $event->element?->id;
    });

    Date::setTestNow('2026-10-01 12:04:59');
    expect(app(ElementStatuses::class)->updateScheduled())->toBe(0);

    // The scheduler missed 12:05, so the 12:06 run catches up
    Date::setTestNow('2026-10-01 12:06:00');
    expect(app(ElementStatuses::class)->updateScheduled())->toBe(1)
        ->and($this->changes)->toBe([[$entry->id, Entry::STATUS_PENDING, Entry::STATUS_LIVE]])
        ->and($invalidatedIds)->toBe([$entry->id])
        ->and(DB::table(Table::ENTRIES)->where('id', $entry->id)->value('status'))->toBe(Entry::STATUS_LIVE);

    Date::setTestNow('2026-10-01 12:07:00');
    expect(app(ElementStatuses::class)->updateScheduled())->toBe(0)
        ->and($this->changes)->toHaveCount(1);
});

it('considers an entry live from the start of its post date’s minute, as entry queries do', function () {
    $entry = ($this->scheduledEntry)('2026-10-01 12:04:45');

    Date::setTestNow('2026-10-01 12:04:30');
    $loaded = Entry::find()->id($entry->id)->status(null)->one();

    expect($loaded->getStatus())->toBe(Entry::STATUS_LIVE)
        ->and(Entry::find()->id($entry->id)->status(Entry::STATUS_LIVE)->exists())->toBeTrue()
        ->and(app(ElementStatuses::class)->updateScheduled())->toBe(1);
});

it('announces a save that changes the status, and not again from the next run', function () {
    $entry = ($this->scheduledEntry)('2026-10-01 12:05:00');

    Date::setTestNow('2026-10-01 12:05:20');
    expect(Elements::saveElement($entry))->toBeTrue()
        ->and($this->changes)->toBe([[$entry->id, Entry::STATUS_PENDING, Entry::STATUS_LIVE]])
        ->and(app(ElementStatuses::class)->updateScheduled())->toBe(0)
        ->and($this->changes)->toHaveCount(1);
});

it('announces a disabled entry going live when it’s enabled, not when its post date passes', function () {
    $entry = ($this->scheduledEntry)('2026-10-01 12:05:00', enabled: false);

    Date::setTestNow('2026-10-01 12:06:00');
    expect(app(ElementStatuses::class)->updateScheduled())->toBe(1)
        ->and($this->changes)->toBe([]);

    $entry->enabled = true;
    expect(Elements::saveElement($entry))->toBeTrue()
        ->and($this->changes)->toBe([[$entry->id, Entry::STATUS_DISABLED, Entry::STATUS_LIVE]]);
});

it('re-saves entries whose statuses changed when staticStatuses is on', function () {
    app(GeneralConfig::class)->staticStatuses = true;

    try {
        $entry = ($this->scheduledEntry)('2026-10-01 12:05:00');
        $saved = [];
        Event::listen(ElementSaved::class, function (ElementSaved $event) use (&$saved) {
            $saved[] = [$event->element->id, $event->element->oldStatus, $event->element->getStatus()];
        });

        Date::setTestNow('2026-10-01 12:06:00');

        expect(app(ElementStatuses::class)->updateScheduled())->toBe(1)
            ->and($saved)->toBe([[$entry->id, Entry::STATUS_PENDING, Entry::STATUS_LIVE]])
            ->and($this->changes)->toBe([[$entry->id, Entry::STATUS_PENDING, Entry::STATUS_LIVE]])
            ->and(Entry::find()->id($entry->id)->status(Entry::STATUS_LIVE)->exists())->toBeTrue();
    } finally {
        app(GeneralConfig::class)->staticStatuses = false;
    }
});

it('doesn’t announce or track drafts and revisions, or the nested entries copied for them', function () {
    $matrixEntryType = EntryType::factory()->create(['hasTitleField' => true]);
    $matrixField = Field::factory()->create([
        'handle' => 'matrixField',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$matrixEntryType->id]],
    ]);
    $ownerModel = EntryModel::factory()
        ->withFieldLayout(FieldLayout::factory()->forField($matrixField))
        ->create();
    $owner = Entry::find()->id($ownerModel->id)->status(null)->one();
    $owner->setFieldValueFromRequest('matrixField', [
        'entries' => ['new1' => ['type' => $matrixEntryType->handle, 'title' => 'Block', 'enabled' => true]],
        'sortOrder' => ['new1'],
    ]);
    expect(Elements::saveElement($owner))->toBeTrue();
    $this->changes = [];

    $userId = User::findOne()->id;
    app(Drafts::class)->createDraft($owner, $userId);
    app(Drafts::class)->createDraft($owner, $userId, provisional: true);
    app(Revisions::class)->createRevision($owner, force: true);

    $copyIds = DB::table(Table::ELEMENTS)->whereNotNull('canonicalId')->pluck('id')->all();
    DB::table(Table::ENTRIES)->whereIn('id', $copyIds)->update(['status' => Entry::STATUS_EXPIRED]);

    expect($copyIds)->not->toBeEmpty()
        ->and($this->changes)->toBe([])
        ->and(app(ElementStatuses::class)->updateScheduled())->toBe(0);
});

it('doesn’t announce nested entries added or edited in a draft', function () {
    $matrixEntryType = EntryType::factory()->create(['hasTitleField' => true]);
    $matrixField = Field::factory()->create([
        'handle' => 'matrixField',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$matrixEntryType->id]],
    ]);
    $ownerModel = EntryModel::factory()
        ->withFieldLayout(FieldLayout::factory()->forField($matrixField))
        ->create();
    $owner = Entry::find()->id($ownerModel->id)->status(null)->one();
    $owner->setFieldValueFromRequest('matrixField', [
        'entries' => ['new1' => ['type' => $matrixEntryType->handle, 'title' => 'Block', 'enabled' => true]],
        'sortOrder' => ['new1'],
    ]);
    expect(Elements::saveElement($owner))->toBeTrue();
    $blockId = $owner->getFieldValue('matrixField')->status(null)->one()->id;
    $this->changes = [];

    $draft = app(Drafts::class)->createDraft($owner, User::findOne()->id, provisional: true);
    $draft->setFieldValueFromRequest('matrixField', [
        'entries' => [
            $blockId => ['type' => $matrixEntryType->handle, 'title' => 'Edited', 'enabled' => true],
            'new1' => ['type' => $matrixEntryType->handle, 'title' => 'New', 'enabled' => true],
        ],
        'sortOrder' => [$blockId, 'new1'],
    ]);
    expect(Elements::saveElement($draft))->toBeTrue();

    $draftBlockIds = DB::table(Table::ENTRIES)->where('primaryOwnerId', $draft->id)->pluck('id')->all();
    DB::table(Table::ENTRIES)->whereIn('id', $draftBlockIds)->update(['status' => Entry::STATUS_PENDING]);
    app(ElementStatuses::class)->updateScheduled();

    expect($draftBlockIds)->toHaveCount(2)
        ->and($this->changes)->toBe([])
        ->and(DB::table(Table::ENTRIES)->whereIn('id', $draftBlockIds)->pluck('status')->unique()->all())->toBe([Entry::STATUS_LIVE]);
});

it('announces status changes in every site when a draft is applied', function () {
    $otherSite = Site::factory()->create();
    Sites::refreshSites();
    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->withSites($otherSite)->create();
    $entry = EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement();
    $entry->setEnabledForSite([Sites::getPrimarySite()->id => true, $otherSite->id => false]);
    expect(Elements::saveElement($entry))->toBeTrue();

    $siteChanges = [];
    Event::listen(ElementStatusChanged::class, function (ElementStatusChanged $event) use (&$siteChanges) {
        $siteChanges[] = [$event->element->siteId, $event->oldStatus, $event->newStatus];
    });

    $draft = app(Drafts::class)->createDraft($entry, User::findOne()->id);
    $draft->setEnabledForSite([Sites::getPrimarySite()->id => true, $otherSite->id => true]);
    expect(Elements::saveElement($draft))->toBeTrue();
    app(Drafts::class)->applyDraft($draft);

    expect($siteChanges)->toBe([[$otherSite->id, Entry::STATUS_DISABLED, Entry::STATUS_LIVE]]);
});

it('announces a status change once when the scheduled task updates it during a save', function () {
    $entry = ($this->scheduledEntry)('2026-10-01 12:05:00');
    Date::setTestNow('2026-10-01 12:05:20');

    $ran = false;
    Event::listen(ElementSaving::class, function () use (&$ran) {
        if (! $ran) {
            $ran = true;
            expect(app(ElementStatuses::class)->updateScheduled())->toBe(1);
        }
    });

    expect(Elements::saveElement($entry))->toBeTrue()
        ->and($ran)->toBeTrue()
        ->and($this->changes)->toBe([[$entry->id, Entry::STATUS_PENDING, Entry::STATUS_LIVE]]);
});

it('announces a status change once when the scheduled task updates it after a save, before the save commits', function () {
    $entry = ($this->scheduledEntry)('2026-10-01 12:05:00');

    DB::transaction(function () use ($entry) {
        expect(Elements::saveElement($entry))->toBeTrue();

        Date::setTestNow('2026-10-01 12:05:20');
        expect(app(ElementStatuses::class)->updateScheduled())->toBe(1);
    });

    expect($this->changes)->toBe([[$entry->id, Entry::STATUS_PENDING, Entry::STATUS_LIVE]]);
});

it('keeps updating other entries when one entry’s re-save fails', function () {
    app(GeneralConfig::class)->staticStatuses = true;

    try {
        $failing = ($this->scheduledEntry)('2026-10-01 12:05:00');
        $healthy = ($this->scheduledEntry)('2026-10-01 12:05:00');
        Event::listen(ElementSaving::class, function (ElementSaving $event) use ($failing) {
            if ($event->element->id === $failing->id) {
                throw new RuntimeException('Simulated save failure.');
            }
        });

        Date::setTestNow('2026-10-01 12:06:00');
        app(ElementStatuses::class)->updateScheduled();

        expect(DB::table(Table::ENTRIES)->where('id', $failing->id)->value('status'))->toBe(Entry::STATUS_PENDING)
            ->and(DB::table(Table::ENTRIES)->where('id', $healthy->id)->value('status'))->toBe(Entry::STATUS_LIVE)
            ->and($this->changes)->toBe([[$healthy->id, Entry::STATUS_PENDING, Entry::STATUS_LIVE]]);
    } finally {
        app(GeneralConfig::class)->staticStatuses = false;
    }
});

it('updates statuses from the craft:update-element-statuses command and the deprecated craft:update-statuses command', function (string $command) {
    $entry = ($this->scheduledEntry)('2026-10-01 12:05:00');
    Date::setTestNow('2026-10-01 12:06:00');

    $this->artisan($command)
        ->expectsOutputToContain('Updated 1 status.')
        ->assertSuccessful();

    expect($this->changes)->toBe([[$entry->id, Entry::STATUS_PENDING, Entry::STATUS_LIVE]]);
})->with([
    'craft:update-element-statuses',
    'craft:update-statuses',
    'craft:update-statuses/index',
    'craft:update-statuses --isolated',
]);

it('logs a deprecation when the command runs by a deprecated name, with or without the craft: prefix', function (string $name, bool $deprecated) {
    Deprecator::shouldReceive('log')->times($deprecated ? 1 : 0);
    $console = new ConsoleApplication(app(), app(Dispatcher::class), 'test');
    $console->setAutoExit(false);
    $console->add(app(UpdateElementStatusesCommand::class));
    $console->add(app(UpdateStatusesCommand::class));

    expect($console->run(new ArrayInput(['command' => $name]), new BufferedOutput))->toBe(0);
})->with([
    ['update-element-statuses', false],
    ['craft:update-element-statuses', false],
    ['update-statuses', true],
    ['update-statuses/index', true],
    ['craft:update-statuses', true],
]);

it('updates entries owned by revisions without re-saving or announcing them when staticStatuses is on', function () {
    app(GeneralConfig::class)->staticStatuses = true;

    try {
        $owner = ($this->scheduledEntry)('2026-10-01 11:00:00');
        $revisionId = app(Revisions::class)->createRevision($owner, force: true);
        $nested = EntryModel::factory()->create([
            'primaryOwnerId' => $revisionId,
            'fieldId' => Field::factory()->create(['type' => Matrix::class])->id,
            'status' => Entry::STATUS_PENDING,
            'postDate' => '2026-10-01 11:00:00',
        ]);
        Exceptions::fake();

        app(ElementStatuses::class)->updateScheduled();

        Exceptions::assertNothingReported();
        expect(DB::table(Table::ENTRIES)->where('id', $nested->id)->value('status'))->toBe(Entry::STATUS_LIVE)
            ->and($this->changes)->toBe([]);
    } finally {
        app(GeneralConfig::class)->staticStatuses = false;
    }
});

it('schedules status updates every minute unless scheduleElementStatusUpdates is off', function () {
    $event = collect(app(Schedule::class)->events())
        ->firstOrFail(fn (ScheduledEvent $event) => $event->description === 'craft:update-element-statuses');
    $config = app(GeneralConfig::class);

    expect($event->expression)->toBe('* * * * *')
        ->and($event->filtersPass(app()))->toBeTrue();

    $config->scheduleElementStatusUpdates = false;

    expect($event->filtersPass(app()))->toBeFalse();
});

it('doesn’t schedule status updates while a Craft update is pending', function () {
    $event = collect(app(Schedule::class)->events())
        ->firstOrFail(fn (ScheduledEvent $event) => $event->description === 'craft:update-element-statuses');
    Updates::partialMock()->shouldReceive('isCraftUpdatePending')->andReturnTrue();

    expect($event->filtersPass(app()))->toBeFalse();
});

it('announces an entry expiring once, from the first run in its expiry date’s minute', function () {
    $entry = ($this->scheduledEntry)('2026-10-01 11:00:00');
    $entry->expiryDate = Date::parse('2026-10-01 12:10:30');
    expect(Elements::saveElement($entry))->toBeTrue();
    $this->changes = [];

    Date::setTestNow('2026-10-01 12:09:59');
    expect(app(ElementStatuses::class)->updateScheduled())->toBe(0);

    Date::setTestNow('2026-10-01 12:10:00');
    expect(app(ElementStatuses::class)->updateScheduled())->toBe(1)
        ->and($this->changes)->toBe([[$entry->id, Entry::STATUS_LIVE, Entry::STATUS_EXPIRED]])
        ->and(DB::table(Table::ENTRIES)->where('id', $entry->id)->value('status'))->toBe(Entry::STATUS_EXPIRED)
        ->and(Entry::find()->id($entry->id)->status(Entry::STATUS_EXPIRED)->exists())->toBeTrue()
        ->and(app(ElementStatuses::class)->updateScheduled())->toBe(0);
});

it('reports re-saves that fail validation when staticStatuses is on', function () {
    app(GeneralConfig::class)->staticStatuses = true;
    $entry = ($this->scheduledEntry)('2026-10-01 12:05:00');
    Event::listen(ElementSaving::class, function (ElementSaving $event) {
        $event->element->errors()->add('title', 'Simulated validation failure.');
        $event->isValid = false;
    });
    Exceptions::fake();

    Date::setTestNow('2026-10-01 12:06:00');

    expect(app(ElementStatuses::class)->updateScheduled())->toBe(0);
    Exceptions::assertReported(fn (InvalidElementException $e) => str_contains($e->getMessage(), (string) $entry->id));
});
