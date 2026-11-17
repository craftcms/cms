<?php

declare(strict_types=1);

use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use yii\base\Event as YiiEvent;
use yii\base\Module;

uses(DatabaseTestCase::class);

beforeEach(function(): void {
    $this->runningInConsole = new ReflectionProperty(app(), 'isRunningInConsole');
    $this->originalRunningInConsole = $this->runningInConsole->getValue(app());
    $this->runningInConsole->setValue(app(), false);

    Craft::$app->setModule('test-plugin', [
        'class' => Module::class,
        'controllerMap' => [
            'form' => ActionFailureTestController::class,
        ],
    ]);

    YiiEvent::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) {
        $event->rules['contact'] = 'test-plugin/form/index';
    });
});

afterEach(function(): void {
    $this->runningInConsole->setValue(app(), $this->originalRunningInConsole);

    YiiEvent::off(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES);
});

it('routes a failed action back through the url rule for the requested path', function(): void {
    $this->post('/contact', ['action' => 'test-plugin/form/save'])
        ->assertOk()
        ->assertSee('form: sent back by the failed action');
});

class ActionFailureTestController extends \craft\web\Controller
{
    public $enableCsrfValidation = false;

    protected array|bool|int $allowAnonymous = true;

    public function actionIndex(string $message = 'none'): string
    {
        return "form: $message";
    }

    public function actionSave(): ?\yii\web\Response
    {
        return $this->asFailure('Couldn’t save.', routeParams: ['message' => 'sent back by the failed action']);
    }
}
