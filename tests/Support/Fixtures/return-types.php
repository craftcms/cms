<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support\Fixtures;

use CraftCms\Cms\Support\Json;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

use function PHPStan\Testing\assertType;

/**
 * @param  array{required: int, nullable: string|null, optional?: int|null, removed: null}  $input
 */
function filterNullableShape(array $input): void
{
    assertType('array{required: int, nullable?: string, optional?: int}', Arr::whereNotNull($input));
    assertType("array{flag: false, count: 0, label: ''}", Arr::whereNotNull([
        'flag' => false,
        'count' => 0,
        'label' => '',
        'removed' => null,
    ]));
}

/**
 * @param  Collection<string, int>  $collection
 */
function collectionKeys(Collection $collection): void
{
    assertType('array<string, int>', $collection->all());
    assertType('list<int>', $collection->values()->all());

    $normalized = $collection->values();
    $normalized->forget(0);

    assertType('array<int, int>', $normalized->all());
}

function jsonFailures(mixed $value): void
{
    assertType('non-empty-string|false', Json::encode($value));
    assertType('non-empty-string|false', Json::encode($value, JSON_UNESCAPED_UNICODE));
    assertType('non-empty-string', Json::encode($value, JSON_THROW_ON_ERROR));
    assertType('non-empty-string', Json::encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    assertType('non-empty-string', Json::encode(options: JSON_THROW_ON_ERROR, value: $value));
}
