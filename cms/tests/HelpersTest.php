<?php

use function CraftCms\Cms\normalizeVersion;

test('normalizeVersion', function (string $expected, string $version) {
    expect(normalizeVersion($version))->toBe($expected);
})->with([
    ['21', 'version 21'],
    ['120.19.2', 'v120.19.2--beta'],
    ['', 'version'],
    ['2', '2\0\0'],
    ['2', '2+2+2'],
    ['2', '2-0-0'],
    ['', '~2'],
    ['', ''],
    ['', '\*v^2.0.0(beta)'],
    ['2.0.0-alpha', '2.0.0-alpha+foo'],
    ['2.0.0-alpha', '2.0.0-alpha.+foo'],
    ['2.0.0-alpha.10', '2.0.0-alpha.10+foo'],
    ['10.5.13', '5.5.5-10.5.13-MariaDB-1:10.5.13+maria~focal-log'],
    ['10.3.38', '10.3.38-MariaDB-1:10.3.38+maria~ubu2004-log'],
    ['5.5.5', '5.5.5-ubuntu-20.04'],
    ['10.3.38', '5.5.5-10.3.38-ubuntu-20.04'],
    ['5.7.16', '5.7.16-0ubuntu0.16.04.1'],
]);
