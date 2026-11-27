<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Field\Matrix;

it('names the entry URI format in its errors', function () {
    $field = new Matrix([
        'name' => 'Blocks',
        'handle' => 'blocks',
        'siteSettings' => [
            ['uriFormat' => Cms::config()->routing->cpTrigger.'/{slug}'],
        ],
    ]);

    $field->validate();

    expect($field->errors()->first('siteSettings.0.uriFormat'))
        ->toBe('Entry URI Format cannot start with the routing.cpTrigger config setting.');
});
