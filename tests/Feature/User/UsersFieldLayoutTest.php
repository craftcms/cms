<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;

it('returns the user field layout', function () {
    $layout = app(Users::class)->getFieldLayout();

    expect($layout->type)->toBe(User::class)
        ->and($layout->id)->toBe(app(Fields::class)->getLayoutByType(User::class)->id);
});
