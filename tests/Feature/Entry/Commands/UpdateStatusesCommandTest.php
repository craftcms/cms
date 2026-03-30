<?php

declare(strict_types=1);

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Events\BeforeSave;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\DateTimeHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    app(GeneralConfig::class)->staticStatuses = true;

    $this->entryType = EntryType::factory()->create();
    $this->section = Section::factory()->withEntryTypes($this->entryType)->create([
        'type' => SectionType::Channel,
    ]);
});

afterEach(function () {
    DateTimeHelper::resume();
});

it('updates stale live, pending, and expired entry statuses', function () {
    DateTimeHelper::pause(new DateTime('2026-03-24 12:00:00', new DateTimeZone('UTC')));

    $live = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->create([
            'status' => EntryElement::STATUS_PENDING,
            'postDate' => '2026-03-24 11:00:00',
            'expiryDate' => null,
        ]);

    $pending = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->create([
            'status' => EntryElement::STATUS_LIVE,
            'postDate' => '2026-03-24 13:00:00',
            'expiryDate' => null,
        ]);

    $expired = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->create([
            'status' => EntryElement::STATUS_LIVE,
            'postDate' => '2026-03-24 10:00:00',
            'expiryDate' => '2026-03-24 11:30:00',
        ]);

    $this->artisan('craft:update-statuses')
        ->expectsOutputToContain('Updating live entries')
        ->expectsOutputToContain('Updating pending entries')
        ->expectsOutputToContain('Updating expired entries')
        ->expectsOutputToContain("Updating entry ({$live->id})")
        ->expectsOutputToContain("Updating entry ({$pending->id})")
        ->expectsOutputToContain("Updating entry ({$expired->id})")
        ->assertSuccessful();

    expect(DB::table(Table::ENTRIES)->where('id', $live->id)->value('status'))->toBe(EntryElement::STATUS_LIVE)
        ->and(DB::table(Table::ENTRIES)->where('id', $pending->id)->value('status'))->toBe(EntryElement::STATUS_PENDING)
        ->and(DB::table(Table::ENTRIES)->where('id', $expired->id)->value('status'))->toBe(EntryElement::STATUS_EXPIRED);
});

it('supports the legacy aliases', function (string $command) {
    $this->artisan($command)->assertSuccessful();
})->with([
    'legacy alias' => 'update-statuses',
    'legacy action alias' => 'update-statuses/index',
]);

it('continues after a save failure and still succeeds', function () {
    DateTimeHelper::pause(new DateTime('2026-03-24 12:00:00', new DateTimeZone('UTC')));

    $failingEntry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->create([
            'status' => EntryElement::STATUS_PENDING,
            'postDate' => '2026-03-24 11:00:00',
        ]);

    $healthyEntry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->create([
            'status' => EntryElement::STATUS_PENDING,
            'postDate' => '2026-03-24 10:30:00',
        ]);

    Event::listen(BeforeSave::class, function (BeforeSave $event) use ($failingEntry) {
        if ($event->element->id !== $failingEntry->id) {
            return;
        }

        $event->element->errors()->add('title', 'Simulated resave failure.');
        $event->isValid = false;
    });

    $this->artisan('craft:update-statuses')
        ->expectsOutputToContain("Updating entry ({$failingEntry->id})")
        ->expectsOutputToContain('failed:')
        ->expectsOutputToContain("Updating entry ({$healthyEntry->id})")
        ->expectsOutputToContain('done')
        ->assertSuccessful();

    expect(DB::table(Table::ENTRIES)->where('id', $failingEntry->id)->value('status'))->toBe(EntryElement::STATUS_PENDING)
        ->and(DB::table(Table::ENTRIES)->where('id', $healthyEntry->id)->value('status'))->toBe(EntryElement::STATUS_LIVE);
});

it('skips entries that already have the correct stored status', function () {
    DateTimeHelper::pause(new DateTime('2026-03-24 12:00:00', new DateTimeZone('UTC')));

    $unchangedEntry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->create([
            'status' => EntryElement::STATUS_LIVE,
            'postDate' => '2026-03-24 11:00:00',
        ]);

    $this->artisan('craft:update-statuses')
        ->doesntExpectOutputToContain("Updating entry ({$unchangedEntry->id})")
        ->assertSuccessful();

    expect(DB::table(Table::ENTRIES)->where('id', $unchangedEntry->id)->value('status'))->toBe(EntryElement::STATUS_LIVE);
});
