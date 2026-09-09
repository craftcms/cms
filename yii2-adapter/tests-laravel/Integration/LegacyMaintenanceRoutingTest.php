<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Middleware\HandleInertiaRequests;
use CraftCms\Cms\Support\Url;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use yii\base\Module;

use function CraftCms\Cms\action_url;

uses(DatabaseTestCase::class);

beforeEach(function(): void {
    Craft::$app->setModule('test-plugin', [
        'class' => Module::class,
        'controllerMap' => [
            'offline' => OfflineTestController::class,
            'protected' => ProtectedTestController::class,
        ],
    ]);
});

afterEach(function(): void {
    app()->maintenanceMode()->deactivate();
});

it('allows guests to reach legacy actions marked anonymous offline during maintenance', function(): void {
    app()->maintenanceMode()->activate([]);

    $this->get(action_url('test-plugin/offline'))
        ->assertOk()
        ->assertSee('offline');
});

it('allows guests to reach CP-prefixed legacy actions marked anonymous offline during maintenance', function(): void {
    $this->withoutMiddleware(HandleInertiaRequests::class);
    request()->attributes->set('isCpRequest', true);
    $actionUrl = Url::actionUrl('test-plugin/offline');
    app()->maintenanceMode()->activate([]);

    $this->get($actionUrl)
        ->assertOk()
        ->assertSee('offline');
});

it('keeps protected legacy actions unavailable to guests during maintenance', function(): void {
    app()->maintenanceMode()->activate([]);
    $this->withoutExceptionHandling();

    $this->get(action_url('test-plugin/protected'));
})->throws(\craft\web\ServiceUnavailableHttpException::class);

it('keeps CP-prefixed protected legacy actions unavailable to guests during maintenance', function(): void {
    $this->withoutMiddleware(HandleInertiaRequests::class);
    request()->attributes->set('isCpRequest', true);
    $actionUrl = Url::actionUrl('test-plugin/protected');
    app()->maintenanceMode()->activate([]);

    $this->get($actionUrl)->assertRedirect();
});

class OfflineTestController extends \craft\web\Controller
{
    protected array|bool|int $allowAnonymous = self::ALLOW_ANONYMOUS_OFFLINE;

    public function actionIndex(): string
    {
        return 'offline';
    }
}

class ProtectedTestController extends \craft\web\Controller
{
    public function actionIndex(): string
    {
        return 'protected';
    }
}
