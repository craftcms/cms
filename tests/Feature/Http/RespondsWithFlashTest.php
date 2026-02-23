<?php

declare(strict_types=1);

use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Validation\Concerns\Validates;
use CraftCms\Cms\Validation\Contracts\Validatable;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\MessageBag;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

class TestFlashController extends Controller
{
    use RespondsWithFlash;

    public function success()
    {
        return $this->asSuccess('Success message', ['key' => 'value']);
    }

    public function failure()
    {
        return $this->asFailure('Failure message', ['error' => 'details']);
    }

    public function successWithRedirect()
    {
        return $this->asSuccess('Success message', [], '/custom-redirect');
    }

    public function modelSuccess()
    {
        $model = new class
        {
            public string $name = 'Test Model';
        };

        return $this->asModelSuccess($model, 'Model saved successfully', 'testModel');
    }

    public function modelFailure()
    {
        $model = new class implements Validatable
        {
            use Validates;

            public string $name = 'Test Model';

            public function errors(): MessageBag
            {
                return new MessageBag(['name' => ['Name is required']]);
            }
        };

        return $this->asModelFailure($model, 'Model save failed', 'testModel');
    }
}

beforeEach(function () {
    Route::post('/test-flash/success', [TestFlashController::class, 'success']);
    Route::post('/test-flash/failure', [TestFlashController::class, 'failure']);
    Route::post('/test-flash/success-redirect', [TestFlashController::class, 'successWithRedirect']);
    Route::post('/test-flash/model-success', [TestFlashController::class, 'modelSuccess']);
    Route::post('/test-flash/model-failure', [TestFlashController::class, 'modelFailure']);

    actingAs(User::findOne());
});

it('asSuccess returns JSON for API request', function () {
    postJson('/test-flash/success')
        ->assertOk()
        ->assertJson(['message' => 'Success message', 'key' => 'value']);
});

it('asFailure returns JSON with 400 for API request', function () {
    postJson('/test-flash/failure')
        ->assertStatus(400)
        ->assertJson(['message' => 'Failure message', 'error' => 'details']);
});

it('asSuccess redirects with flash for HTML request', function () {
    post('/test-flash/success')
        ->assertRedirect();
});

it('asFailure redirects with flash for HTML request', function () {
    post('/test-flash/failure')
        ->assertRedirect();
});

it('asSuccess with custom redirect uses the redirect URL', function () {
    post('/test-flash/success-redirect')
        ->assertRedirect('/custom-redirect');
});

it('asModelSuccess returns JSON with model data for API request', function () {
    postJson('/test-flash/model-success')
        ->assertOk()
        ->assertJson([
            'message' => 'Model saved successfully',
            'modelName' => 'testModel',
        ]);
});

it('asModelFailure returns JSON with errors for API request', function () {
    postJson('/test-flash/model-failure')
        ->assertStatus(400)
        ->assertJson([
            'message' => 'Model save failed',
            'modelName' => 'testModel',
            'errors' => ['name' => ['Name is required']],
        ]);
});
