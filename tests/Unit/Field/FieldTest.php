<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Field;
use Illuminate\Support\Facades\Date;

test('serializeValue() serializes Carbon dates as ISO-8601 strings', function () {
    $field = new class extends Field {};

    expect($field->serializeValue(Date::create(2026, 11, 8, 9, 30, 0, 'Europe/Brussels'), null))
        ->toBe('2026-11-08T09:30:00+01:00');
});
