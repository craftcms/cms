<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Events\AfterDelete;
use CraftCms\Cms\Element\Events\BeforeDelete;
use CraftCms\Cms\Element\Models\Element as ElementModel;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use Illuminate\Support\Facades\Event;

it('soft deletes an element', function () {
    Event::fake([BeforeDelete::class, AfterDelete::class]);

    $entry = EntryModel::factory()->createElement();

    $this->artisan('elements/delete', ['id' => $entry->id, '--no-interaction' => true])
        ->expectsOutputToContain('Element deleted.')
        ->assertSuccessful();

    expect(ElementModel::withTrashed()->find($entry->id)?->dateDeleted)->not()->toBeNull();

    Event::assertDispatchedOnce(BeforeDelete::class);
    Event::assertDispatchedOnce(AfterDelete::class);
});

it('hard deletes an element', function () {
    $entry = EntryModel::factory()->createElement();

    $this->artisan('craft:elements:delete', ['id' => $entry->id, '--hard' => true, '--no-interaction' => true])
        ->expectsOutputToContain('Element deleted.')
        ->assertSuccessful();

    expect(ElementModel::withTrashed()->find($entry->id))->toBeNull();
});

it('blocks deleting a single section entry', function () {
    $section = Section::factory()->create([
        'type' => SectionType::Single,
    ]);

    $entry = EntryModel::factory()->forSection($section)->createElement();

    $this->artisan('craft:elements:delete', ['id' => $entry->id])
        ->expectsOutputToContain('Deleting single section entries is not allowed.')
        ->assertExitCode(1);
});

it('fails when soft deleting an already soft-deleted element', function () {
    $entry = EntryModel::factory()->createElement();

    Elements::deleteElement($entry);

    $this->artisan('craft:elements:delete', ['id' => $entry->id])
        ->expectsOutputToContain('already soft-deleted')
        ->assertExitCode(1);
});
