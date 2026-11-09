<?php

declare(strict_types=1);

use craft\events\ExceptionEvent;
use craft\web\ErrorHandler;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use yii\base\Event as YiiEvent;
use yii\base\Module;
use yii\web\ForbiddenHttpException;

use function CraftCms\Cms\action_url;

uses(DatabaseTestCase::class);

beforeEach(function(): void {
    Craft::$app->setModule('test-plugin', [
        'class' => Module::class,
        'controllerMap' => [
            'http-exception' => HttpExceptionTestController::class,
        ],
    ]);
});

it('returns 403 when a guest requests a protected legacy action', function(): void {
    $this->postJson(action_url('test-plugin/http-exception/protected'))
        ->assertForbidden();
});

it('returns 400 when a legacy action is missing a required param', function(): void {
    $this->postJson(action_url('test-plugin/http-exception/requires-param'))
        ->assertBadRequest()
        ->assertJsonPath('message', 'Request missing required body param');
});

it('passes the original Yii exception to legacy before handle exception handlers', function(): void {
    $receivedException = null;

    $handler = function(ExceptionEvent $event) use (&$receivedException) {
        $receivedException = $event->exception;
    };

    YiiEvent::on(ErrorHandler::class, ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION, $handler);

    try {
        $this->postJson(action_url('test-plugin/http-exception/protected'));
    } finally {
        YiiEvent::off(ErrorHandler::class, ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION, $handler);
    }

    expect($receivedException)->toBeInstanceOf(ForbiddenHttpException::class);
});

class HttpExceptionTestController extends \craft\web\Controller
{
    public $enableCsrfValidation = false;

    protected array|bool|int $allowAnonymous = ['requires-param'];

    public function actionProtected(): string
    {
        return 'protected';
    }

    public function actionRequiresParam(): string
    {
        return $this->request->getRequiredBodyParam('entryId');
    }
}
