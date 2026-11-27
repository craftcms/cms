<?php

declare(strict_types=1);

use craft\fields\Categories;
use craft\fields\Tags;
use CraftCms\Yii2Adapter\Tests\TestCase;

uses(TestCase::class);

it('gives each generic class every template type in phpType()', function(string $fieldClass) {
    preg_match_all('/\\\\([A-Za-z_][\\w\\\\]*)(?:<([^<>]+)>)?/', $fieldClass::phpType(), $types, PREG_SET_ORDER);

    expect($types)->not->toBeEmpty();

    foreach ($types as $type) {
        preg_match_all('/@template\\s+\\w+/', (string)(new ReflectionClass($type[1]))->getDocComment(), $templates);
        $arguments = isset($type[2]) ? count(explode(',', $type[2])) : 0;

        expect($arguments)->toBe(count($templates[0]), sprintf('%s::phpType() gives %s %d generic arguments', $fieldClass, $type[1], $arguments));
    }
})->with([
    'Categories' => [Categories::class],
    'Tags' => [Tags::class],
]);
