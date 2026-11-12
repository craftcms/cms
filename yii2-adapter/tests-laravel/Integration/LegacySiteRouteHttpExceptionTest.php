<?php

declare(strict_types=1);

use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use yii\base\Event as YiiEvent;
use yii\base\Module;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

uses(DatabaseTestCase::class);

beforeEach(function(): void {
    $this->runningInConsole = new ReflectionProperty(app(), 'isRunningInConsole');
    $this->originalRunningInConsole = $this->runningInConsole->getValue(app());
    $this->runningInConsole->setValue(app(), false);

    Craft::$app->setModule('test-plugin', [
        'class' => Module::class,
        'controllerMap' => [
            'things' => SiteRouteHttpExceptionTestController::class,
        ],
    ]);

    YiiEvent::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) {
        $event->rules['things/missing'] = 'test-plugin/things/missing';
        $event->rules['things/forbidden'] = 'test-plugin/things/forbidden';
    });
});

afterEach(function(): void {
    $this->runningInConsole->setValue(app(), $this->originalRunningInConsole);

    YiiEvent::off(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES);
});

it('preserves the status of Yii HTTP exceptions thrown by site route actions', function(string $path, int $status): void {
    $this->get($path)->assertStatus($status);
})->with([
    'not found' => ['/things/missing', 404],
    'forbidden' => ['/things/forbidden', 403],
]);

class SiteRouteHttpExceptionTestController extends \craft\web\Controller
{
    protected array|bool|int $allowAnonymous = true;

    public function actionMissing(): never
    {
        throw new NotFoundHttpException();
    }

    public function actionForbidden(): never
    {
        throw new ForbiddenHttpException();
    }
}
