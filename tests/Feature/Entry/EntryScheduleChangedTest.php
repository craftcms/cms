<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Events\ElementScheduleChanged;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Date::setTestNow('2026-10-01 12:00:00');
    $this->events = [];
    Event::listen(
        ElementScheduleChanged::class,
        function (object $event) {
            $this->events[] = $event;
        },
    );
    $this->of = fn (string $class) => array_values(array_filter($this->events, fn ($e) => $e instanceof $class));
});

afterEach(fn () => Date::setTestNow());

function scheduledEntry(): EntryElement
{
    $entry = EntryModel::factory()->createElement(['title' => 'Scheduled']);
    Sections::refreshSections();
    EntryTypes::refreshEntryTypes();

    return $entry;
}

it('reports upcoming and cancelled dates when live entries are saved, not drafts', function () {
    $entry = scheduledEntry();
    $this->events = [];

    // Past post date change: nothing scheduled
    $entry->postDate = Date::parse('2026-09-30 12:00:00');
    expect(Elements::saveElement($entry))->toBeTrue();
    expect(($this->of)(ElementScheduleChanged::class))->toHaveCount(0);

    // Future expiry added
    $entry->expiryDate = Date::parse('2026-10-02 08:00:00');
    expect(Elements::saveElement($entry))->toBeTrue();
    $changed = ($this->of)(ElementScheduleChanged::class);
    expect($changed)->toHaveCount(1)
        ->and(array_map(fn ($d) => $d->getTimestamp(), $changed[0]->upcomingDates()))->toBe([Date::parse('2026-10-02 08:00:00')->getTimestamp()])
        ->and($changed[0]->cancelledDates())->toBe([]);

    // Unchanged resave: no event (old dates read back from the DB compare equal)
    $this->events = [];
    $fresh = EntryElement::find()->id($entry->id)->status(null)->one();
    expect(Elements::saveElement($fresh))->toBeTrue();
    expect(($this->of)(ElementScheduleChanged::class))->toHaveCount(0);

    // Expiry moved: old one reported as cancelled
    $fresh->expiryDate = Date::parse('2026-10-03 08:00:00');
    expect(Elements::saveElement($fresh))->toBeTrue();
    $changed = ($this->of)(ElementScheduleChanged::class);
    expect($changed)->toHaveCount(1)
        ->and(array_map(fn ($d) => $d->getTimestamp(), $changed[0]->upcomingDates()))->toBe([Date::parse('2026-10-03 08:00:00')->getTimestamp()])
        ->and(array_map(fn ($d) => $d->getTimestamp(), $changed[0]->cancelledDates()))->toBe([Date::parse('2026-10-02 08:00:00')->getTimestamp()]);

    // Draft with a future post date: nothing
    $this->events = [];
    $draft = app(Drafts::class)->createDraft($fresh, User::findOne()->id, 'Draft');
    $draft->postDate = Date::parse('2026-11-01 00:00:00');
    expect(Elements::saveElement($draft))->toBeTrue();
    expect(($this->of)(ElementScheduleChanged::class))->toHaveCount(0);

    // Applying the draft does
    app(Drafts::class)->applyDraft($draft);
    $changed = ($this->of)(ElementScheduleChanged::class);
    expect($changed)->toHaveCount(1)
        ->and($changed[0]->element->id)->toBe($fresh->id)
        ->and(array_map(fn ($d) => $d->getTimestamp(), $changed[0]->upcomingDates()))->toBe([Date::parse('2026-10-03 08:00:00')->getTimestamp(), Date::parse('2026-11-01 00:00:00')->getTimestamp()]);
});

it('does not report a schedule change from a rolled-back save', function () {
    $entry = scheduledEntry();
    $this->events = [];

    DB::beginTransaction();
    $entry->postDate = Date::parse('2026-10-05 00:00:00');
    expect(Elements::saveElement($entry))->toBeTrue();
    DB::rollBack();

    expect(($this->of)(ElementScheduleChanged::class))->toHaveCount(0);
});

it('reports a new entry saved with a future post date', function () {
    $existing = scheduledEntry();
    $entry = new EntryElement;
    $entry->sectionId = $existing->sectionId;
    $entry->typeId = $existing->typeId;
    $entry->title = 'New pending';
    $entry->postDate = Date::parse('2026-10-09 09:00:00');
    $this->events = [];

    expect(Elements::saveElement($entry))->toBeTrue();
    $changed = ($this->of)(ElementScheduleChanged::class);
    expect($changed)->toHaveCount(1)
        ->and(array_map(fn ($d) => $d->getTimestamp(), $changed[0]->upcomingDates()))->toBe([Date::parse('2026-10-09 09:00:00')->getTimestamp()]);
});
