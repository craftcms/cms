<?php

declare(strict_types=1);

use craft\fields\Categories;
use craft\fields\Tags;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Yii2Adapter\Tests\TestCase;

uses(TestCase::class);

it('gives ElementCollection every template type in phpType()', function(string $fieldClass) {
    preg_match_all('/@template\s+\w+/', (string)(new ReflectionClass(ElementCollection::class))->getDocComment(), $templates);
    $collection = preg_quote(ElementCollection::class, '/');

    expect(preg_match("/\\\\{$collection}<([^<>]+)>/", $fieldClass::phpType(), $match))->toBe(1)
        ->and(count(explode(',', $match[1])))->toBe(count($templates[0]));
})->with([
    'Categories' => [Categories::class],
    'Tags' => [Tags::class],
]);
