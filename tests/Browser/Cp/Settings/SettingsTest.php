<?php

declare(strict_types=1);

use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(fn () => actingAs(User::first()));

it('groups the settings', function () {
    visitCp('settings')
        ->assertSeeIn('#main >> role=heading[level=2] >> nth=0', 'System')
        ->assertSeeIn('#main >> role=heading[level=2] >> nth=1', 'Content')
        ->assertSeeIn('#main >> role=heading[level=2] >> nth=2', 'Extend');
});

it('links to each settings page', function (string $item, string $heading) {
    visitCp('settings')
        ->click("#main >> role=link[name=\"$item - Settings\"]")
        ->assertSeeIn('#main h1', $heading);
})->with([
    'General' => ['General', 'General Settings'],
    'Sites' => ['Sites', 'Sites'],
    'Routes' => ['Routes', 'Routes'],
    'Sections' => ['Sections', 'Sections'],
    'Fields' => ['Fields', 'Fields'],
]);
