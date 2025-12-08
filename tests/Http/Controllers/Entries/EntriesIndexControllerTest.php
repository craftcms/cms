<?php

declare(strict_types=1);

use CraftCms\Cms\User\Elements\User;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('requires login', function () {
    get(cp_url('content'))->assertRedirect(cp_url('login'));
});

it('redirects to the first page', function () {
    actingAs(User::find()->one());

    expect(get(cp_url('content')))->assertRedirect(cp_url('content/entries'));
    expect(get(cp_url('entries')))->assertRedirect(cp_url('content/entries'));
});
