<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\Dashboard\DashboardController;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\AssertionFailedError;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withHeaders;

beforeEach(function () {
    actingAs(User::findOne());

    Route::middleware('web')->post('/test-messages/{response}', function (string $response) {
        request()->attributes->set('isCpRequest', true);
        Flash::success('Entry saved.', target: 'save');

        return $response === 'redirect' ? back() : response('Saved');
    });
});

it('finds messages flashed for the page a redirect leads to', function () {
    post('/test-messages/redirect')
        ->assertRedirect()
        ->assertCpMessage('success', 'Entry saved.')
        ->assertCpMessage(fn (array $message): bool => $message['target'] === 'save')
        ->assertNoCpMessages('error');
});

it('ignores messages a response leaves in the session for a later page', function () {
    post('/test-messages/page')
        ->assertOk()
        ->assertNoCpMessages();
});

it('finds messages in Inertia pages', function (bool $inertiaVisit) {
    $url = action([DashboardController::class, 'index']);
    $version = get($url)->assertNoCpMessages()->inertiaPage()['version'];

    swapUrlRequest(cp_url('dashboard'));
    Flash::success('Entry saved.');

    withHeaders($inertiaVisit ? ['X-Inertia' => 'true', 'X-Inertia-Version' => $version] : [])
        ->get($url)
        ->assertOk()
        ->assertCpMessage('success', 'Entry saved.');
})->with([
    'full page load' => false,
    'Inertia visit' => true,
]);

it('lists the delivered messages when an assertion fails', function () {
    $response = post('/test-messages/redirect');

    expect(fn () => $response->assertCpMessage('error'))
        ->toThrow(AssertionFailedError::class, "The response didn’t deliver a [error] message. Delivered messages:\n- [success] Entry saved.")
        ->and(fn () => $response->assertCpMessage('success', 'Entry deleted.'))
        ->toThrow(AssertionFailedError::class, 'the [success] message “Entry deleted.”')
        ->and(fn () => $response->assertNoCpMessages('success'))
        ->toThrow(AssertionFailedError::class, '- [success] Entry saved.');
});
