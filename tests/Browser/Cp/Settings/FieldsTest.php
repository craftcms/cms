<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(fn () => actingAs(User::first()));

it('creates a plain text field and keeps editing it', function () {
    $page = visitCp('settings/fields')
        ->click('#cp-content-header >> role=link[name="New field"]')
        ->type('input[name="name"]', 'My Plain Text Field')
        ->assertValue('input[name="handle"]', 'myPlainTextField')
        ->click('#main >> role=button[name="Actions"]')
        ->click('role=button[name=/^Save and continue editing/]')
        ->assertSeeIn('#main h1', 'My Plain Text Field');

    $field = Field::query()->where('handle', 'myPlainTextField')->firstOrFail();

    expect($field->name)->toBe('My Plain Text Field')
        ->and($field->type)->toBe(PlainText::class);

    $page->assertPathEndsWith("/settings/fields/edit/$field->id");
});
