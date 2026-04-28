<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Data\JsonData;

it('returns null for missing method calls without parameters', function () {
    $data = new JsonData(['foo' => 'bar']);

    expect($data->missingMethod())->toBeNull();
});

it('throws for missing method calls with parameters', function () {
    $data = new JsonData(['foo' => 'bar']);

    $data->missingMethod('value');
})->throws(BadMethodCallException::class);
