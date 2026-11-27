<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Dashboard\DashboardController;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateRoots;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;
use PHPUnit\Framework\AssertionFailedError;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\pageTemplate;
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

it('finds messages flashed for the page an Inertia hard visit leads to', function () {
    Route::middleware('web')->post('/test-messages-hard-visit', function () {
        request()->attributes->set('isCpRequest', true);
        Flash::success('Entry saved.');

        return Inertia::location('/elsewhere');
    });

    withHeaders(['X-Inertia' => 'true'])
        ->post('/test-messages-hard-visit')->assertConflict()
        ->assertCpMessage('success', 'Entry saved.');
});

it('finds messages a Twig page queues for its legacy message display', function (Closure $render) {
    app(TemplateRoots::class)->register(TemplateMode::Cp, 'test-plugin', dirname(__DIR__, 2).'/Support/templates');

    Route::middleware(['craft', 'craft.web', 'craft.cp', 'auth', 'can:accessCp'])
        ->prefix(Cms::config()->cpTrigger)
        ->get('test-messages-document/{flash}', function (string $flash) use ($render) {
            if ($flash === 'yes') {
                Flash::error('Couldn’t save “Draft]);” </script>.');
            }

            return $render();
        });

    get(cp_url('test-messages-document/no'))
        ->assertOk()
        ->assertNoCpMessages();

    get(cp_url('test-messages-document/yes'))
        ->assertOk()
        ->assertCpMessage('error', 'Couldn’t save “Draft]);” </script>.');
})->with([
    'page template' => fn () => pageTemplate('test-plugin/document'),
    'view response' => function () {
        View::addLocation(dirname(__DIR__, 2).'/Support/templates');

        return response()->view('document');
    },
]);

it('ignores messages a response leaves in the session for a later page', function () {
    post('/test-messages/page')
        ->assertOk()
        ->assertNoCpMessages();

    Route::middleware('web')->get('/test-messages-download', fn () => response()->streamDownload(fn () => print ('file'), 'file.txt'));

    get('/test-messages-download')
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
