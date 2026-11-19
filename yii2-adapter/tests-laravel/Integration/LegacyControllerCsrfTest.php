<?php

declare(strict_types=1);

use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use CraftCms\Cms\User\Models\User;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use yii\base\Event as YiiEvent;
use yii\base\Module;

uses(DatabaseTestCase::class);

beforeEach(function(): void {
    // Laravel skips CSRF verification while running unit tests in the console.
    $this->runningInConsole = new ReflectionProperty(app(), 'isRunningInConsole');
    $this->originalRunningInConsole = $this->runningInConsole->getValue(app());
    $this->runningInConsole->setValue(app(), false);

    CsrfProtectedTestController::$beforeActionCalls = 0;

    Craft::$app->setModule('test-plugin', [
        'class' => Module::class,
        'controllerMap' => [
            'webhook' => CsrfOptOutTestController::class,
            'hook' => CsrfBeforeActionOptOutTestController::class,
            'form' => CsrfProtectedTestController::class,
        ],
    ]);

    YiiEvent::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) {
        $event->rules['api/webhook'] = 'test-plugin/webhook/handle';
        $event->rules['api/form'] = 'test-plugin/form/handle';
    });

    YiiEvent::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
        $event->rules['test-plugin/webhook'] = 'test-plugin/webhook/handle';
        $event->rules['test-plugin/hook'] = 'test-plugin/hook/handle';
        $event->rules['test-plugin/form'] = 'test-plugin/form/handle';
    });
});

afterEach(function(): void {
    $this->runningInConsole->setValue(app(), $this->originalRunningInConsole);
    PreventRequestForgery::useOriginOnly(false);

    YiiEvent::off(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES);
    YiiEvent::off(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES);
});

it('honors a legacy controller’s CSRF opt-out', function(bool $originOnly, string $path, int $status): void {
    PreventRequestForgery::useOriginOnly($originOnly);

    $this->postJson($path)->assertStatus($status);
})->with([
    'site rule, opted out' => [false, '/api/webhook', 200],
    'site rule, protected' => [false, '/api/form', 419],
    'site rule, opted out, origin-only' => [true, '/api/webhook', 200],
    'site rule, protected, origin-only' => [true, '/api/form', 403],
    'action, opted out' => [false, '/actions/test-plugin/webhook/handle', 200],
    'action, opted out in beforeAction()' => [false, '/actions/test-plugin/hook/handle', 200],
    'action, protected' => [false, '/actions/test-plugin/form/handle', 419],
    'action, opted out, origin-only' => [true, '/actions/test-plugin/webhook/handle', 200],
    'action, opted out in beforeAction(), origin-only' => [true, '/actions/test-plugin/hook/handle', 200],
    'action, protected, origin-only' => [true, '/actions/test-plugin/form/handle', 403],
    'control panel action, opted out in beforeAction()' => [false, '/admin/actions/test-plugin/hook/handle', 200],
    'control panel action, protected' => [false, '/admin/actions/test-plugin/form/handle', 419],
]);

it('honors a legacy controller’s CSRF opt-out for control panel URL rules', function(string $path, int $status): void {
    $this->actingAs(User::factory()->admin()->create());

    $this->postJson($path)->assertStatus($status);
})->with([
    'opted out' => ['/admin/test-plugin/webhook', 200],
    'opted out in beforeAction()' => ['/admin/test-plugin/hook', 200],
    'protected' => ['/admin/test-plugin/form', 419],
]);

it('accepts token-less requests whose Origin matches the host when Sec-Fetch-Site is missing', function(bool $originOnly): void {
    PreventRequestForgery::useOriginOnly($originOnly);

    $this->withHeader('Origin', rtrim(url('/'), '/'))
        ->postJson('/api/form')
        ->assertOk();
})->with(['default mode' => false, 'origin-only mode' => true]);

it('runs a legacy controller’s beforeAction() once for a request that passes CSRF validation', function(): void {
    $this->withHeader('Sec-Fetch-Site', 'same-origin')
        ->postJson('/actions/test-plugin/form/handle')
        ->assertOk();

    expect(CsrfProtectedTestController::$beforeActionCalls)->toBe(1);
});

it('keeps validating CSRF tokens for actions a legacy controller doesn’t opt out of after it opts out of another', function(): void {
    $module = Craft::$app->getModule('test-plugin');

    $this->postJson('/actions/test-plugin/hook/handle')->assertOk();

    // Requests reset the Yii app once they’re done.
    app('Craft')->setModule('test-plugin', $module);

    $this->postJson('/actions/test-plugin/hook/other')->assertStatus(419);
});

class CsrfOptOutTestController extends \craft\web\Controller
{
    public $enableCsrfValidation = false;

    protected array|bool|int $allowAnonymous = true;

    public function actionHandle(): \yii\web\Response
    {
        return $this->asJson(['ok' => true]);
    }
}

class CsrfBeforeActionOptOutTestController extends \craft\web\Controller
{
    protected array|bool|int $allowAnonymous = true;

    public function beforeAction($action): bool
    {
        if ($action->id === 'handle') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionHandle(): \yii\web\Response
    {
        return $this->asJson(['ok' => true]);
    }

    public function actionOther(): \yii\web\Response
    {
        return $this->asJson(['ok' => true]);
    }
}

class CsrfProtectedTestController extends \craft\web\Controller
{
    public static int $beforeActionCalls = 0;

    protected array|bool|int $allowAnonymous = true;

    public function beforeAction($action): bool
    {
        self::$beforeActionCalls++;

        return parent::beforeAction($action);
    }

    public function actionHandle(): \yii\web\Response
    {
        return $this->asJson(['ok' => true]);
    }
}
