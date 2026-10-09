<?php

declare(strict_types=1);

use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Validation\Concerns\Validates;
use CraftCms\Cms\Validation\Contracts\Validatable;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;
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
        return $this->asFailure('Failure message', ['error' => 'details', 'key' => 'value']);
    }

    public function failureWithoutData()
    {
        return $this->asFailure('Failure message');
    }

    public function failureWithErrors()
    {
        return $this->asFailure('Failure message', ['errors' => ['name' => ['Name is required']]]);
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

            public function setAttributes(array $values): void
            {
                $this->name = $values['name'] ?? '';
            }
        };

        return $this->asModelFailure($model, 'Model save failed', 'testModel');
    }
}

beforeEach(function () {
    Route::middleware('web')->post('/test-flash/success', [TestFlashController::class, 'success']);
    Route::middleware('web')->post('/test-flash/failure', [TestFlashController::class, 'failure']);
    Route::middleware('web')->post('/test-flash/failure-without-data', [TestFlashController::class, 'failureWithoutData']);
    Route::middleware('web')->post('/test-flash/failure-with-errors', [TestFlashController::class, 'failureWithErrors']);
    Route::middleware('web')->post('/test-flash/success-redirect', [TestFlashController::class, 'successWithRedirect']);
    Route::middleware('web')->post('/test-flash/model-success', [TestFlashController::class, 'modelSuccess']);
    Route::middleware('web')->post('/test-flash/model-failure', [TestFlashController::class, 'modelFailure']);

    actingAs(User::findOne());
});

it('asSuccess returns JSON for API request', function () {
    postJson('/test-flash/success')
        ->assertOk()
        ->assertJson(['message' => 'Success message', 'key' => 'value']);
});

it('asFailure returns JSON with 400 for API request', function () {
    postJson('/test-flash/failure')->assertBadRequest()
        ->assertJson(['message' => 'Failure message', 'error' => 'details']);
});

it('asSuccess redirects with flash for HTML request', function () {
    post('/test-flash/success')
        ->assertRedirect()
        ->assertSessionHas('success', 'Success message');
});

it('asFailure redirects with flash for HTML request', function () {
    post('/test-flash/failure', ['title' => 'Posted title'])
        ->assertRedirect()
        ->assertSessionHas('error', 'Failure message')
        ->assertSessionHas('key', 'value')
        ->assertSessionHasInput('title', 'Posted title');
});

it('asFailure puts validation messages in the error bag and keeps its summary message', function () {
    post('/test-flash/failure-with-errors')
        ->assertRedirect()
        ->assertSessionHasErrors(['name' => 'Name is required'])
        ->assertMessage('error', 'Failure message');
});

it('asSuccess with custom redirect uses the redirect URL', function () {
    post('/test-flash/success-redirect')
        ->assertRedirect('/custom-redirect');
});

it('asSuccess decrypts posted success messages before flashing them', function () {
    post('/test-flash/success-redirect', [
        'successMessage' => Crypt::encrypt('Thanks for filling out the survey!'),
    ])
        ->assertRedirect('/custom-redirect')
        ->assertSessionHas('success', 'Thanks for filling out the survey!');
});

it('asFailure decrypts posted fail messages before flashing them', function () {
    post('/test-flash/failure-without-data', [
        'failMessage' => Crypt::encrypt('Something went wrong.'),
    ])
        ->assertRedirect()
        ->assertSessionHas('error', 'Something went wrong.');
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
    postJson('/test-flash/model-failure')->assertBadRequest()
        ->assertJson([
            'message' => 'Model save failed',
            'modelName' => 'testModel',
            'errors' => ['name' => ['Name is required']],
        ]);
});

it('returns the message in the body of control panel JSON responses without flashing it', function () {
    Route::middleware('web')->post('/test-flash/cp-success', function () {
        request()->attributes->set('isCpRequest', true);

        return (new TestFlashController)->success();
    });

    $response = postJson('/test-flash/cp-success')
        ->assertOk()
        ->assertJson([
            'message' => 'Success message',
            'messages' => [[
                'type' => 'success',
                'message' => 'Success message',
            ]],
        ]);

    expect($response->json('notificationSettings.id'))->toBe($response->json('messages.0.id'))
        ->and(session()->get(Flash::SESSION_KEY))->toBeNull();
});

it('flashes control panel messages to the message list', function () {
    Route::middleware('web')->post('/test-flash/cp-redirect', function () {
        request()->attributes->set('isCpRequest', true);

        return (new TestFlashController)->success();
    });

    post('/test-flash/cp-redirect')
        ->assertRedirect()
        ->assertSessionMissing('success')
        ->assertMessage('success', 'Success message');
});
