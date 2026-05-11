<?php

use yii\base\Module;
use yii\web\Controller;

it('routes plugin default controller action shorthand', function() {
    Craft::$app->setModule('test-plugin', [
        'class' => Module::class,
        'controllerMap' => [
            'default' => TestPluginDefaultController::class,
        ],
    ]);

    $response = Craft::$app->runAction('test-plugin/ping');

    expect($response->data)->toBe('pong');
});

it('accepts null responses from plugin default controller action shorthand', function() {
    Craft::$app->setModule('test-plugin', [
        'class' => Module::class,
        'controllerMap' => [
            'default' => TestPluginDefaultController::class,
        ],
    ]);

    $response = Craft::$app->runAction('test-plugin/null');

    expect($response)->toBeNull();
});

class TestPluginDefaultController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionPing(): string
    {
        return 'pong';
    }

    public function actionNull(): null
    {
        return null;
    }
}
