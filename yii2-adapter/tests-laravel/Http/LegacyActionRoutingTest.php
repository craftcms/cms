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

class TestPluginDefaultController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionPing(): string
    {
        return 'pong';
    }
}
