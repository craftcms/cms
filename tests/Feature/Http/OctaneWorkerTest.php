<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Http\Controllers\PreviewController;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Octane\ApplicationFactory;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Octane;
use Laravel\Octane\Testing\Fakes\FakeClient;
use Laravel\Octane\Testing\Fakes\FakeWorker;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

/**
 * Serves the requests one after another from a single Octane worker booted with the test's application.
 *
 * @param  list<Request>  $requests
 * @return list<Response>
 */
function serveFromOctaneWorker(array $requests): array
{
    $factory = new class(app()) extends ApplicationFactory
    {
        public function __construct(private readonly Application $app) {}

        /**
         * @param  array<string, mixed>  $initialInstances
         */
        public function createApplication(array $initialInstances = []): Application
        {
            foreach ($initialInstances as $abstract => $instance) {
                $this->app->instance($abstract, $instance);
            }

            return $this->app;
        }
    };

    $worker = new FakeWorker($factory, $client = new FakeClient($requests));
    $worker->boot();

    try {
        $worker->run();
    } finally {
        // Octane leaves services pointing at the last request's flushed sandbox until the next request replaces it.
        $event = new RequestReceived(app(), app(), app('request'));

        foreach (Octane::prepareApplicationForNextOperation() as $listener) {
            app($listener)->handle($event);
        }
    }

    expect($client->errors)->toBe([]);

    return $client->responses;
}

it('does not show a previewed draft to later requests', function () {
    $entry = Entry::factory()->create();

    actingAs(User::find()->one());
    SessionAuth::authorize("previewElement:$entry->id");

    $token = postJson(action([PreviewController::class, 'createToken']), [
        'elementType' => EntryElement::class,
        'siteId' => Site::firstOrFail()->id,
        'canonicalId' => $entry->id,
    ])->json('token');

    Route::get('/octane-preview', fn () => Elements::getElementById($entry->id, EntryElement::class)?->previewing
        ? 'previewing'
        : 'live');

    $responses = serveFromOctaneWorker([
        Request::create("/octane-preview?token=$token"),
        Request::create('/octane-preview'),
    ]);

    expect($responses[0]->getContent())->toBe('previewing')
        ->and($responses[1]->getContent())->toBe('live');
});

it('includes the legacy asset bundles on every control panel page', function () {
    $loginUrl = '/'.Cms::config()->cpTrigger.'/login';

    $responses = serveFromOctaneWorker([
        Request::create($loginUrl),
        Request::create($loginUrl),
    ]);

    foreach ($responses as $response) {
        expect($response->getStatusCode())->toBe(200)
            ->and($response->getContent())->toContain('Craft.registeredAssetBundles');
    }
});
