<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Middleware\AddLogContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use Monolog\LogRecord;

beforeEach(function () {
    $this->handler = new TestHandler;
    Log::driver()->getLogger()->pushHandler($this->handler);

    $this->handlePost = function () {
        $request = Request::create('/contact', 'POST', [
            'email' => 'jane@example.com',
            'password' => 'hunter2',
        ]);
        app()->instance('request', $request);

        app(AddLogContext::class)->handle($request, function () {
            Log::warning('Something failed.');
            Log::error('Something else failed.');

            return 'ok';
        });

        return collect($this->handler->getRecords())->mapWithKeys(fn (LogRecord $record) => [$record->message => $record->context]);
    };
});

it('adds the redacted request body to the first warning or worse only', function () {
    config(['app.debug' => false]);

    $contexts = ($this->handlePost)();

    expect($contexts->keys()->all())->toBe(['Request received.', 'Something failed.', 'Something else failed.'])
        ->and($contexts['Request received.'])->toHaveKey('environment')->not->toHaveKey('body')
        ->and($contexts['Something failed.']['body'])->toBe([
            'email' => 'jane@example.com',
            'password' => '•••••••',
        ])
        ->and($contexts['Something else failed.'])->not->toHaveKey('body');
});

it('adds the redacted request body to the first record in debug mode', function () {
    config(['app.debug' => true]);

    $contexts = ($this->handlePost)();

    expect($contexts->keys()->all())->toBe(['Request received.', 'Something failed.', 'Something else failed.'])
        ->and($contexts['Request received.']['body'])->toBe([
            'email' => 'jane@example.com',
            'password' => '•••••••',
        ])
        ->and($contexts['Something failed.'])->not->toHaveKey('body')
        ->and($contexts['Something else failed.'])->not->toHaveKey('body');
});
