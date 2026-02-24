<?php

declare(strict_types=1);

test('renders the page', function () {
    $this->loginAndVisitCp('settings/general')
        ->assertTitleContains('General Settings')
        ->assertSee('General Settings');
});

test('displays error messages', function () {
    $this->loginAndVisitCp('settings/general')
        ->clear('System Name')
        ->clear('System Status')
        ->type('Retry Duration', 'Cheese')
        ->clear('Time Zone')
        ->press('Save')
        ->assertSee('System Name')
        ->assertSeeIn('[name="name"] .error-list', '')
        ->assertSeeIn('[name="retryDuration"] .error-list', '');
});

test('saves settings', function () {
    $this->loginAndVisitCp('settings/general')
        ->type('System Name', 'Test Name')
        ->type('Retry Duration', '600')
        ->press('Save')
        ->assertSee('System settings saved.');
});
