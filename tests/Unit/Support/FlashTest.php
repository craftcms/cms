<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Flash;
use Illuminate\Http\Request;

function flashRequest(bool $cp, array $headers = []): void
{
    $request = Request::create('/test');
    $request->attributes->set('isCpRequest', $cp);

    foreach ($headers as $name => $value) {
        $request->headers->set($name, $value);
    }

    app()->instance('request', $request);
}

it('collects control panel messages into one list with ids and default settings', function () {
    flashRequest(cp: true);

    Flash::success('Entry saved.');
    Flash::notice('Draft applied.', ['icon' => 'draft']);

    $messages = Flash::all();

    expect($messages)->toHaveCount(2)
        ->and($messages[0])->toMatchArray([
            'type' => 'success',
            'message' => 'Entry saved.',
            'settings' => ['icon' => 'check', 'iconLabel' => 'Success'],
            'target' => null,
        ])
        ->and($messages[1]['settings'])->toBe(['icon' => 'draft', 'iconLabel' => 'Notice'])
        ->and($messages[0]['id'])->not->toBe($messages[1]['id'])
        ->and(session()->has('success'))->toBeFalse();
});

it('keeps plain session keys for site requests', function () {
    flashRequest(cp: false);

    Flash::error('Could not sign in.');

    expect(session()->get('error'))->toBe('Could not sign in.')
        ->and(session()->has(Flash::SESSION_KEY))->toBeFalse()
        ->and(Flash::getError())->toBe('Could not sign in.');
});

it('includes messages flashed straight to plain keys on control panel requests', function () {
    flashRequest(cp: true);

    Flash::success('Site saved.');
    session()->flash('success', 'Site saved.');
    session()->flash('error', 'Plugin error.');

    $messages = Flash::all();

    expect(array_column($messages, 'message'))->toBe(['Site saved.', 'Plugin error.'])
        ->and(Flash::all()[1]['id'])->toBe($messages[1]['id'])
        ->and(Flash::getError())->toBe('Plugin error.');
});

it('returns the latest message of a type from the getters', function () {
    flashRequest(cp: true);

    Flash::success('First.');
    Flash::success('Second.');

    expect(Flash::getSuccess())->toBe('Second.')
        ->and(Flash::getNotice())->toBeNull();
});

it('targets messages at the inline outlet the request names', function () {
    flashRequest(cp: true, headers: [Flash::TARGET_HEADER => 'email-test']);

    Flash::success('Email sent.');
    Flash::error('Explicit.', target: 'login');

    expect(array_column(Flash::all(), 'target'))->toBe(['email-test', 'login']);
});

it('ignores malformed target headers', function () {
    flashRequest(cp: true, headers: [Flash::TARGET_HEADER => '<script>']);

    Flash::success('Saved.');

    expect(Flash::all()[0]['target'])->toBeNull();
});
