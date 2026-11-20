<?php

declare(strict_types=1);

use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(fn () => actingAs(User::first()));

it('marks the current account page in the account navigation', function () {
    visitCp('myaccount')
        ->assertAttribute('nav[aria-label="Secondary"] >> role=link[name="Profile"]', 'aria-current', 'page')
        ->click('nav[aria-label="Secondary"] >> role=link[name="Preferences"]')
        ->assertSeeIn('#main h1', 'Preferences')
        ->assertAttribute('nav[aria-label="Secondary"] >> role=link[name="Preferences"]', 'aria-current', 'page')
        ->assertAttribute('nav[aria-label="Secondary"] >> role=link[name="Profile"]', 'aria-current', 'false');
});

it('saves the user’s profile', function () {
    visitCp('myaccount')
        ->assertValue('input[name="username"]', 'craftcms')
        ->assertValue('input[name="email"]', 'support@craftcms.com')
        ->type('input[name="fullName"]', 'Ada Lovelace')
        ->click('#main >> role=button[name="Save"]')
        ->assertSee('User saved.');

    expect(User::first()->fullName)->toBe('Ada Lovelace');
});

describe('with the workbench content', function () {
    beforeEach(fn () => seedWorkbenchContent());

    it('renders the account pages', function (string $path, string $heading) {
        assertCpPageRenders($path, $heading);
    })->with([
        'my account' => ['myaccount', 'My Account'],
        'preferences' => ['myaccount/preferences', 'Preferences'],
        'addresses' => ['myaccount/addresses', 'Addresses'],
        'ai assistant' => ['myaccount/ai-assistant', 'AI Assistant'],
        'passkeys' => ['myaccount/passkeys', 'Passkeys'],
        'password' => ['myaccount/password', 'Password & Verification'],
        'permissions' => ['myaccount/permissions', 'Permissions'],
        'tokens' => ['myaccount/tokens', 'Tokens'],
    ]);
});
