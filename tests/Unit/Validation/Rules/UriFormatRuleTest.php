<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Validation\Rules\UriFormatRule;
use Illuminate\Support\Facades\Validator;

it('names the attribute in its messages', function (Closure $uriFormat, bool $requireSlug, Closure $expected) {
    $validator = Validator::make(
        ['uriFormat' => $uriFormat()],
        ['uriFormat' => [new UriFormatRule(requireSlug: $requireSlug)]],
        attributes: ['uriFormat' => 'Entry URI Format'],
    );

    expect($validator->errors()->first('uriFormat'))->toBe($expected());
})->with([
    'missing slug' => [
        fn () => 'news',
        true,
        fn () => 'Entry URI Format must contain “{slug}”',
    ],
    'starts with the action trigger' => [
        fn () => Cms::config()->routing->actionTrigger.'/{slug}',
        false,
        fn () => 'Entry URI Format cannot start with the routing.actionTrigger config setting.',
    ],
    'starts with the control panel trigger' => [
        fn () => Cms::config()->routing->cpTrigger.'/{slug}',
        false,
        fn () => 'Entry URI Format cannot start with the routing.cpTrigger config setting.',
    ],
]);
