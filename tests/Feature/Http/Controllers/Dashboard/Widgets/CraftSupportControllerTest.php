<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Dashboard\Widgets\CraftSupport;
use CraftCms\Cms\Http\Controllers\Dashboard\Widgets\CraftSupportController;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\User\Elements\User;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\from;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::find()->one());

    $this->dashboard = app(Dashboard::class);
    $this->mock(Api::class)->shouldReceive('request')->andReturn(new Response(new PsrResponse(200)));
});

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
