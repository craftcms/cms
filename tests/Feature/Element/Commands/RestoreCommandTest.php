<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Events\AfterRestore;
use CraftCms\Cms\Element\Events\BeforeRestore;
use CraftCms\Cms\Element\Models\Element as ElementModel;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Support\Facades\Elements;
use Illuminate\Support\Facades\Event;

it('restores a soft-deleted element', function () {
    Event::fake([BeforeRestore::class, AfterRestore::class]);

    $entry = EntryModel::factory()->createElement();
    Elements::deleteElement($entry);

    $this->artisan('elements/restore', ['id' => $entry->id])
        ->expectsOutputToContain('Element restored.')
        ->assertSuccessful();

    expect(ElementModel::withTrashed()->find($entry->id)?->dateDeleted)->toBeNull();

    Event::assertDispatchedOnce(BeforeRestore::class);
    Event::assertDispatchedOnce(AfterRestore::class);
});

it('fails when the element is already restored', function () {
    $entry = EntryModel::factory()->createElement();

    $this->artisan('craft:elements:restore', ['id' => $entry->id])
        ->expectsOutputToContain('already restored')
        ->assertExitCode(1);
});
