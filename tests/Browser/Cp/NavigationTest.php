<?php

declare(strict_types=1);

use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(fn () => actingAs(User::first()));

it('takes each primary navigation item to its page', function (string $item, string $heading) {
    visitCp('myaccount')
        ->click("nav[aria-label=\"Primary\"] >> role=link[name=\"$item\"]")
        ->assertSeeIn('#main h1', $heading);
})->with([
    'Dashboard' => ['Dashboard', 'Dashboard'],
    'Users' => ['Users', 'Users'],
    'Utilities' => ['Utilities', 'System Report'],
    'Settings' => ['Settings', 'Settings'],
]);

it('marks the current page in the primary navigation', function () {
    visitCp('settings')
        ->assertAttribute('nav[aria-label="Primary"] >> role=link[name="Settings"]', 'aria-current', 'page');
});

it('focuses the skip link first', function () {
    visitCp('dashboard')
        ->keys('html > body', 'Tab')
        ->assertScript('document.activeElement.textContent.trim()', 'Skip to main section');
});

it('moves focus to the main section from the skip link', function () {
    visitCp('dashboard')
        ->keys('role=link[name="Skip to main section"]', 'Enter')
        ->assertScript('document.activeElement.id', 'main');
});
