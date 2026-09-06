<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Dashboard\Widgets\CraftSupport;
use CraftCms\Cms\Database\Backups;
use CraftCms\Cms\Http\Controllers\Dashboard\Widgets\CraftSupportController;
use CraftCms\Cms\License\License;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\Support\Composer;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\User\Elements\User;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\from;
use function Pest\Laravel\mock;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Http::fake();
    actingAs(User::find()->one());

    $this->dashboard = app(Dashboard::class);
    $this->mock(Api::class)->shouldReceive('request')->andReturn(new Response(new GuzzleResponse(200)));
});

it('sends the completed message and cleans up its archive', function (?string $failure, bool $apiFails) {
    $this->dashboard->saveWidget($widget = $this->dashboard->createWidget(CraftSupport::class));
    if ($failure === 'backup') {
        mock(Backups::class)->shouldReceive('backup')->once()->andThrow(new RuntimeException('Backup failed'));
    }

    $zipPath = null;
    $zipExists = false;
    $parts = collect();
    $api = mock(Api::class);
    $api->shouldReceive('request')->once()->with('POST', 'support', Mockery::type('array'))
        ->andReturnUsing(function (string $method, string $uri, array $options) use ($apiFails, &$parts, &$zipPath, &$zipExists) {
            $parts = collect($options['multipart']);
            $archive = $parts->firstWhere('name', 'attachments[0]');
            if ($archive !== null) {
                $zipPath = stream_get_meta_data($archive['contents'])['uri'];
                $zipExists = is_file($zipPath);
            }

            if ($apiFails) {
                throw new RuntimeException('API failed');
            }

            return new Response(new GuzzleResponse(200));
        });

    if ($failure === 'zip') {
        $controller = Mockery::mock(CraftSupportController::class, [app(Composer::class), app(License::class), $api, app(Backups::class)])->makePartial();
        $controller->shouldReceive('createZip')->once()->andThrow(new RuntimeException('Zip failed'));
        app()->instance(CraftSupportController::class, $controller);
    }

    $response = postJson(action(CraftSupportController::class), [
        'widgetId' => $widget->id,
        'fromEmail' => 'sender@example.com',
        'message' => 'Original support message',
        'attachLogs' => false,
        'attachDbBackup' => $failure === 'backup',
        'attachTemplates' => false,
        'attachAdditionalFile' => UploadedFile::fake()->createWithContent('additional.txt', 'Extra details'),
    ]);

    // CONFLICT-REVIEW: 6.x asserted `assertOk()` + `assertSee('success: 0|1')` here, but this branch's
    // controller now redirects to the dashboard with a `success` flash and throws a `support`
    // ValidationException when the API call fails. Kept 6.x's payload/cleanup assertions below and
    // re-expressed only the response assertion in terms of the Inertia behavior, matching the
    // expectations already used by the other tests in this file.
    if ($apiFails) {
        $response->assertUnprocessable()->assertJsonValidationErrors('support');
    } else {
        $response->assertRedirect(route('craft.cp.dashboard'))->assertSessionHas('success');
    }

    $messages = $parts->where('name', 'message');
    expect($messages)->toHaveCount(1);
    $diagnostic = match ($failure) {
        'zip' => "\n\n---\n\nError creating zip: Zip failed",
        'backup' => "\n\n---\n\nError adding database backup: Backup failed",
        default => '',
    };
    expect($messages->first()['contents'])->toBe('Original support message'.$diagnostic);
    expect($parts->firstWhere('name', 'email')['contents'])->toBe('sender@example.com');
    expect($parts->firstWhere('name', 'attachments[1]')['filename'])->toBe('additional.txt');
    expect($zipExists)->toBe($failure !== 'zip');

    if ($zipPath !== null) {
        expect(is_file($zipPath))->toBeFalse();
    }
})->with([null, 'zip', 'backup'])->with([false, true]);

it('requires login', function () {
    Auth::logout();

    postJson(action(CraftSupportController::class))
        ->assertUnauthorized();
});

it('requires a widget id', function () {
    postJson(action(CraftSupportController::class))
        ->assertJsonValidationErrorFor('widgetId');
});

it('validates data after widget id', function (array $data, array $errors) {
    $this->dashboard->saveWidget($widget = $this->dashboard->createWidget(CraftSupport::class));

    $response = postJson(action(CraftSupportController::class), array_merge(['widgetId' => $widget->id], $data));

    if (count($errors) === 0) {
        $response->assertRedirect(route('craft.cp.dashboard'))->assertSessionHas('success');

        return;
    }

    $response->assertUnprocessable()->assertJsonValidationErrors($errors);
})->with([
    [
        'data' => [
            'fromEmail' => 'support@craftcms.com',
            'message' => 'test',
        ],
        'errors' => [],
    ],
    [
        'data' => [
            'fromEmail' => 'not-an-email',
            'message' => 'test',
        ],
        'errors' => ['fromEmail'],
    ],
    [
        'data' => [
            'fromEmail' => 'support@craftcms.com',
        ],
        'errors' => ['message'],
    ],
    [
        'data' => [
            'fromEmail' => 'support@craftcms.com',
            'message' => 'test',
            'attachLogs' => 'not-a-boolean',
        ],
        'errors' => ['attachLogs'],
    ],
    [
        'data' => [
            'fromEmail' => 'support@craftcms.com',
            'message' => 'test',
            'attachDbBackup' => 'not-a-boolean',
        ],
        'errors' => ['attachDbBackup'],
    ],
    [
        'data' => [
            'fromEmail' => 'support@craftcms.com',
            'message' => 'test',
            'attachTemplates' => 'not-a-boolean',
        ],
        'errors' => ['attachTemplates'],
    ],
]);

it('sanitizes attachment names used in support archives', function () {
    $attachment = UploadedFile::fake()->create('unsafe?.zip', 1, 'application/zip');
    $zipData = app(CraftSupportController::class)->createZip(false, false, false, $attachment);
    $zip = new ZipArchive;

    try {
        expect($zip->open($zipData['zipPath']))->toBeTrue()
            ->and($zip->locateName('unsafe.zip'))->not->toBeFalse()
            ->and($zip->locateName('unsafe?.zip'))->toBeFalse();
    } finally {
        $zip->close();
        File::delete($zipData['zipPath']);
    }
});

it('redirects back with validation errors for the Inertia support form', function () {
    from(route('craft.cp.dashboard'))
        ->post(action(CraftSupportController::class), ['widgetId' => 1])
        ->assertRedirect(route('craft.cp.dashboard'))
        ->assertSessionHasErrors(['fromEmail', 'message']);
});

it('redirects back with an error when sending fails', function () {
    $this->mock(Api::class)->shouldReceive('request')->andThrow(new RuntimeException('Unavailable'));

    from(route('craft.cp.dashboard'))
        ->post(action(CraftSupportController::class), [
            'widgetId' => 1,
            'fromEmail' => 'support@example.com',
            'message' => 'Test message',
            'attachAdditionalFile' => UploadedFile::fake()->create('details.txt', 1, 'text/plain'),
            'attachLogs' => '0',
        ])
        ->assertRedirect(route('craft.cp.dashboard'))
        ->assertSessionHasErrors('support');
});
