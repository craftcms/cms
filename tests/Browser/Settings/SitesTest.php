<?php

declare(strict_types=1);

test('displays the index', function () {
    $this->loginAndVisitCp('settings/sites')
        ->assertTitleContains('Sites')
        ->assertSee('Sites');
});

test('creates a new site', function () {
    $this->loginAndVisitCp('settings/sites/new')
        ->assertTitleContains('Create a new site')
        ->assertSee('Create a new site')
        ->type('Name', 'New Site')
        ->press('Save')
        ->assertPathEndsWith('settings/sites');
})->skip('Name field is not a standard input element');
