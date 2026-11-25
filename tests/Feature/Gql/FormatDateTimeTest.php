<?php

declare(strict_types=1);

use CraftCms\Cms\Gql\Directives\FormatDateTime;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;

function applyFormatDateTime(string $date, array $arguments): mixed
{
    return FormatDateTime::apply(null, new DateTime($date, new DateTimeZone('UTC')), $arguments, Mockery::mock(ResolveInfo::class));
}

it('outputs only the date or time', function (string $date, array $arguments, string $expected) {
    expect(applyFormatDateTime($date, $arguments))->toBe($expected);
})->with([
    'date' => ['2026-11-24 13:45:10', ['output' => 'date', 'timezone' => 'UTC'], '2026-11-24'],
    'date with a PHP format' => ['2026-11-24 13:45:10', ['output' => 'date', 'format' => 'd/m/Y', 'timezone' => 'UTC'], '24/11/2026'],
    'time' => ['2026-11-24 13:45:10', ['output' => 'time', 'timezone' => 'UTC'], '13:45:10+00:00'],
    'time with a PHP format' => ['2026-11-24 13:45:10', ['output' => 'time', 'format' => 'H:i', 'timezone' => 'UTC'], '13:45'],
    'date with the locale’s medium format' => ['2026-11-24 13:45:10', ['output' => 'date', 'format' => 'medium', 'locale' => 'en-US', 'timezone' => 'UTC'], 'Nov 24, 2026'],
    'time with the locale’s medium format' => ['2026-11-24 13:45:10', ['output' => 'time', 'format' => 'medium', 'locale' => 'de', 'timezone' => 'UTC'], '13:45:10'],
    'date and time by default' => ['2026-11-24 13:45:10', ['timezone' => 'UTC'], '2026-11-24T13:45:10+00:00'],
]);

it('rejects an unknown output', function () {
    applyFormatDateTime('2026-11-24 13:45:10', ['output' => 'week']);
})->throws(UserError::class, 'Invalid output: week');
