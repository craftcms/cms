<?php

declare(strict_types=1);

use CraftCms\Cms\Validation\Rules\TimeRule;
use Illuminate\Support\Facades\Validator;

it('validates time values', function (mixed $value) {
    expect(Validator::make(['time' => $value], ['time' => new TimeRule]))->passes()->toBeTrue();
})->with([
    'hour and minute' => ['14:30'],
    'hour minute and second' => ['14:30:15'],
    'localized time' => ['2:30 PM'],
    'date time instance' => [new DateTime('2024-01-01 14:30:00')],
]);

it('fails invalid time values', function (mixed $value) {
    $error = null;

    new TimeRule(message: 'Invalid time.')->validate('time', $value, function (string $message) use (&$error) {
        $error = $message;
    });

    expect($error)->toBe('Invalid time.');
})->with([
    'null' => [null],
    'empty string' => [''],
    'text' => ['not-a-time'],
    'malformed time' => ['12:not'],
]);

it('validates minimum times inclusively', function (string $value, bool $expected) {
    $validator = Validator::make([
        'time' => $value,
    ], [
        'time' => [new TimeRule(
            min: '09:00',
            tooEarly: 'Too early. Minimum is {min}.',
        )],
    ]);

    expect($validator->passes())->toBe($expected);

    if (! $expected) {
        expect($validator->errors()->first('time'))->toBe("Too early. Minimum is 9:00\u{202F}AM.");
    }
})->with([
    'before minimum' => ['08:59', false],
    'at minimum' => ['09:00', true],
    'after minimum' => ['09:01', true],
]);

it('validates maximum times inclusively', function (string $value, bool $expected) {
    $validator = Validator::make([
        'time' => $value,
    ], [
        'time' => [new TimeRule(
            max: '17:00',
            tooLate: 'Too late. Maximum is {max}.',
        )],
    ]);

    expect($validator->passes())->toBe($expected);

    if (! $expected) {
        expect($validator->errors()->first('time'))->toBe("Too late. Maximum is 5:00\u{202F}PM.");
    }
})->with([
    'before maximum' => ['16:59', true],
    'at maximum' => ['17:00', true],
    'after maximum' => ['17:01', false],
]);

it('validates normal time ranges', function (string $value, bool $expected, ?string $message = null) {
    $validator = Validator::make([
        'time' => $value,
    ], [
        'time' => [new TimeRule(
            min: '09:00',
            max: '17:00',
            tooEarly: 'Too early. Minimum is {min}.',
            tooLate: 'Too late. Maximum is {max}.',
        )],
    ]);

    expect($validator->passes())->toBe($expected);

    if ($message !== null) {
        expect($validator->errors()->first('time'))->toBe($message);
    }
})->with([
    'before range' => ['08:59', false, "Too early. Minimum is 9:00\u{202F}AM."],
    'start of range' => ['09:00', true],
    'inside range' => ['12:00', true],
    'end of range' => ['17:00', true],
    'after range' => ['17:01', false, "Too late. Maximum is 5:00\u{202F}PM."],
]);

it('validates overnight time ranges', function (string $value, bool $expected) {
    $validator = Validator::make([
        'time' => $value,
    ], [
        'time' => [new TimeRule(
            min: '22:00',
            max: '02:00',
            outOfRange: 'Must be between {min} and {max}.',
        )],
    ]);

    expect($validator->passes())->toBe($expected);

    if (! $expected) {
        expect($validator->errors()->first('time'))->toBe("Must be between 10:00\u{202F}PM and 2:00\u{202F}AM.");
    }
})->with([
    'before overnight maximum' => ['01:59', true],
    'at overnight maximum' => ['02:00', true],
    'outside overnight range' => ['12:00', false],
    'at overnight minimum' => ['22:00', true],
    'after overnight minimum' => ['23:00', true],
]);

it('can resolve minimum and maximum times from other validated data', function (string $value, bool $expected) {
    $validator = Validator::make([
        'time' => $value,
        'startsAt' => '09:00',
        'endsAt' => '17:00',
    ], [
        'time' => [new TimeRule(
            min: 'startsAt',
            max: 'endsAt',
        )],
    ]);

    expect($validator->passes())->toBe($expected);
})->with([
    'before referenced range' => ['08:59', false],
    'inside referenced range' => ['12:00', true],
    'after referenced range' => ['17:01', false],
]);

it('returns itself when setting data', function () {
    $rule = new TimeRule;

    expect($rule->setData(['startsAt' => '09:00']))->toBe($rule);
});

it('throws when configured with an invalid minimum time', function () {
    $validator = Validator::make([
        'time' => '12:00',
    ], [
        'time' => [new TimeRule(min: 'not-a-time')],
    ]);

    $validator->passes();
})->throws(RuntimeException::class, 'Invalid minimum time:');

it('throws when configured with an invalid maximum time', function () {
    $validator = Validator::make([
        'time' => '12:00',
    ], [
        'time' => [new TimeRule(max: 'not-a-time')],
    ]);

    $validator->passes();
})->throws(RuntimeException::class, 'Invalid maximum time:');
