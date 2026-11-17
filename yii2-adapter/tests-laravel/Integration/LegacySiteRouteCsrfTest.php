<?php

declare(strict_types=1);

use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
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

    Craft::$app->setModule('test-plugin', [
        'class' => Module::class,
        'controllerMap' => [
            'webhook' => SiteRouteCsrfOptOutTestController::class,
            'form' => SiteRouteCsrfProtectedTestController::class,
        ],
    ]);

    YiiEvent::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) {
        $event->rules['api/webhook'] = 'test-plugin/webhook/handle';
        $event->rules['api/form'] = 'test-plugin/form/handle';
    });
});

afterEach(function(): void {
    $this->runningInConsole->setValue(app(), $this->originalRunningInConsole);
    PreventRequestForgery::useOriginOnly(false);

    YiiEvent::off(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES);
});

it('honors a legacy controller’s CSRF opt-out for site URL rules', function(bool $originOnly, string $path, int $status): void {
    PreventRequestForgery::useOriginOnly($originOnly);

    $this->postJson($path)->assertStatus($status);
})->with([
    'opted out' => [false, '/api/webhook', 200],
    'protected' => [false, '/api/form', 419],
    'opted out, origin-only' => [true, '/api/webhook', 200],
    'protected, origin-only' => [true, '/api/form', 403],
]);

it('accepts token-less requests whose Origin matches the host when Sec-Fetch-Site is missing', function(bool $originOnly): void {
    PreventRequestForgery::useOriginOnly($originOnly);

    $this->withHeader('Origin', rtrim(url('/'), '/'))
        ->postJson('/api/form')
        ->assertOk();
})->with(['default mode' => false, 'origin-only mode' => true]);

class SiteRouteCsrfOptOutTestController extends \craft\web\Controller
{
    public $enableCsrfValidation = false;

    protected array|bool|int $allowAnonymous = true;

    public function actionHandle(): \yii\web\Response
    {
        return $this->asJson(['ok' => true]);
    }
}

class SiteRouteCsrfProtectedTestController extends \craft\web\Controller
{
    protected array|bool|int $allowAnonymous = true;

    public function actionHandle(): \yii\web\Response
    {
        return $this->asJson(['ok' => true]);
    }
}
