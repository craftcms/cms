<?php

declare(strict_types=1);

test('renders the page', function () {
    $this->loginAndVisitCp('utilities/system-messages')
        ->assertSee('System Messages');
})->skip('Requires utility:system-messages permission setup');

test('shows the edit modal', function () {
    $this->loginAndVisitCp('utilities/system-messages')
        ->click('[data-testid="btn-edit-message"]')
        ->assertSee('Edit Message');
})->skip('Requires utility:system-messages permission setup');

test('handles multisite')->skip();

test('updates the messages', function () {
    $subject = 'Subject '.uniqid();

    $this->loginAndVisitCp('utilities/system-messages')
        ->click('[data-testid="btn-edit-message"]')
        ->type('Subject', $subject)
        ->press('Save')
        ->assertSee($subject);
})->skip('Requires utility:system-messages permission setup');
