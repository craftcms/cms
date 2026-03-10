<?php

declare(strict_types=1);

use CraftCms\Cms\ProjectConfig\ProjectConfig;

beforeEach(function () {
    $this->page = visitCpAsAdmin('settings/general');
});

it('renders the page', function () {
    $this->page->assertSee('General Settings')
        ->assertTitleContains('General Settings');
});

it('displays error messages', function () {
    $this->page->assertSee('General Settings')
        ->fill('#name input', '')
        ->fill('#retry-duration input', 'Cheese')
        ->fill('#time-zone input', '')
        ->fill('#live input', '')
        ->click('Save');

    $this->page->assertSee('Could not save settings')
        ->assertVisible('craft-callout[variant="danger"]')
        ->assertSee('The name field is required.')
        ->assertSee('The live field is required.')
        ->assertSee('The retry duration field must be an integer.');
});

it('saves settings', function () {
    $this->page->assertSee('General Settings')
        ->fill('#name input', 'Test Name')
        ->fill('#retry-duration input', '600');

    // Select "Offline" status via combobox
    $this->page->fill('#live input', 'Off')
        ->keys('#live input', 'ArrowDown')
        ->keys('#live input', 'Enter');

    // Select UTC timezone via combobox
    $this->page->fill('#time-zone input', 'UTC')
        ->keys('#time-zone input', 'ArrowDown')
        ->keys('#time-zone input', 'Enter');

    $this->page->click('Save')
        ->assertSee('System settings saved.');

    $settings = app(ProjectConfig::class)->get('system') ?? [];
    expect($settings)->toMatchArray([
        'name' => 'Test Name',
        'live' => false,
        'timeZone' => 'UTC',
        'retryDuration' => 600,
    ]);
});
