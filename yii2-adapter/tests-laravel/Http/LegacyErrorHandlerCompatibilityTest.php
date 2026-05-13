<?php

use craft\events\ExceptionEvent;
use craft\web\ErrorHandler;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use yii\base\Event as YiiEvent;
use yii\web\HttpException;

it('triggers legacy before handle exception handlers for Laravel 404s', function() {
    $receivedException = null;

    $handler = function(ExceptionEvent $event) use (&$receivedException) {
        $receivedException = $event->exception;
    };

    YiiEvent::on(ErrorHandler::class, ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION, $handler);

    try {
        $response = app(ExceptionHandlerContract::class)->render(
            Request::create('/definitely-missing'),
            new NotFoundHttpException('Page not found.')
        );
    } finally {
        YiiEvent::off(ErrorHandler::class, ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION, $handler);
    }

    expect($response->getStatusCode())->toBe(404);
    expect($receivedException)
        ->toBeInstanceOf(HttpException::class)
        ->and($receivedException->statusCode)->toBe(404);
});
