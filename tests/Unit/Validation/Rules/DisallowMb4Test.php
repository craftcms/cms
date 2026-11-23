<?php

declare(strict_types=1);

use CraftCms\Cms\Validation\Rules\DisallowMb4;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Validator;

it('names the attribute in its message', function () {
    Context::addHidden('craft.supportsMb4', false);

    $validator = Validator::make(
        ['title' => 'Launch 🚀'],
        ['title' => [new DisallowMb4]],
        attributes: ['title' => 'Title'],
    );

    expect($validator->errors()->first('title'))->toBe('Title cannot contain emoji.');
});
