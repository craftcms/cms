<?php

declare(strict_types=1);

use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Field\Addresses;
use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Field\Country;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Json;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Money;
use CraftCms\Cms\Field\Range;
use CraftCms\Cms\Field\Users;
use CraftCms\Cms\Tests\TestClasses\Element\Eloquent\CustomersField;

/**
 * Whether a value satisfies a phpType() union, ignoring generic arguments.
 */
function satisfiesPhpType(mixed $value, string $phpType): bool
{
    do {
        $phpType = preg_replace('/<[^<>]*>/', '', $phpType, -1, $count);
    } while ($count > 0);

    foreach (explode('|', $phpType) as $type) {
        $matches = match ($type = ltrim(trim($type), '\\')) {
            'mixed' => true,
            'null' => $value === null,
            'int' => is_int($value),
            'float' => is_float($value),
            'string' => is_string($value),
            'bool' => is_bool($value),
            'array' => is_array($value),
            default => $value instanceof $type,
        };

        if ($matches) {
            return true;
        }
    }

    return false;
}

test('normalized values satisfy phpType()', function (Closure $field, mixed $value) {
    $field = $field();
    $normalized = $field->normalizeValue($value, null);

    expect(satisfiesPhpType($normalized, $field::phpType()))
        ->toBeTrue(sprintf('%s normalized to %s, which %s::phpType() (%s) does not allow', $field::class, get_debug_type($normalized), $field::class, $field::phpType()));
})->with([
    'Json with data' => [fn () => new Json, ['foo' => 'bar']],
    'Json without data' => [fn () => new Json, null],
    'Country with a code' => [fn () => new Country, 'NL'],
    'Country without a code' => [fn () => new Country, null],
    'Range with an integer' => [fn () => new Range, '5'],
    'Range with a decimal' => [fn () => new Range, '5.5'],
    'Range without a value' => [fn () => new Range, null],
    'Money with an amount' => [fn () => new Money, ['value' => '12.34']],
    'Money without an amount' => [fn () => new Money, null],
]);

test('relational phpType() strings give ElementCollection every template type', function (string $fieldClass) {
    preg_match_all('/@template\s+\w+/', (string) new ReflectionClass(ElementCollection::class)->getDocComment(), $templates);
    $collection = preg_quote(ElementCollection::class, '/');

    expect(preg_match("/\\\\{$collection}<([^<>]+)>/", $fieldClass::phpType(), $match))->toBe(1)
        ->and(count(explode(',', $match[1])))->toBe(count($templates[0]));
})->with([
    Assets::class,
    Entries::class,
    Users::class,
    Matrix::class,
    Addresses::class,
    BaseRelationField::class,
    CustomersField::class,
]);
