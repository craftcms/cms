<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Number;
use CraftCms\Cms\Field\Range;

it('renders a fractional placeholder within the configured bounds', function (string $fieldClass, array $config, string $expected) {
    $field = new $fieldClass($config);

    expect($field->previewPlaceholderHtml(null, null))->toBe($expected);
})->with([
    'number with decimal bounds' => [Number::class, ['min' => 0.26, 'max' => 0.26, 'decimals' => 2], '0.26'],
    'range with a fractional step' => [Range::class, ['min' => 0.25, 'max' => 0.25, 'step' => 0.25], '0.25'],
]);
