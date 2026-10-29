<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Models\User;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;

beforeEach(function (): void {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    $this->user = User::factory()->withPermissions(['accessCp'])->create(['admin' => false]);
    Passport::actingAs($this->user, ['mcp:write'], 'craft-mcp');

    $this->call = fn (string $name, array $arguments = []): TestResponse => McpRequest::send($this, 'tools/call', [
        'name' => $name,
        'arguments' => $arguments,
    ])->assertOk();
});

it('changes the user’s language and formatting locale, keeping other preferences', function (): void {
    Users::saveUserPreferences($this->user, ['locale' => 'en-GB', 'theme' => 'dark']);

    ($this->call)('preferences.update', ['language' => 'de'])
        ->assertJsonPath('result.structuredContent', ['language' => 'de', 'locale' => 'en-GB']);
    ($this->call)('preferences.update', ['locale' => null])
        ->assertJsonPath('result.structuredContent', ['language' => 'de', 'locale' => null]);

    expect(Users::getUserPreferences($this->user->id))->toMatchArray(['language' => 'de', 'locale' => null, 'theme' => 'dark']);
});

it('refuses languages and locales Craft doesn’t support', function (array $arguments, string $message): void {
    ($this->call)('preferences.update', $arguments)
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', fn (string $text): bool => str_starts_with($text, $message));

    expect(Users::getUserPreferences($this->user->id))->not->toHaveKeys(['language', 'locale']);
})->with([
    'language' => [['language' => 'xx'], 'Unknown language [xx]. Available languages: '],
    'locale' => [['locale' => 'xx-YY'], 'Unknown locale [xx-YY].'],
]);
