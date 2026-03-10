<?php

declare(strict_types=1);

it('can load the login page', function () {
    configureBrowserUrls();

    $this->visit('/admin/login')
        ->assertSee('Username or Email')
        ->assertSee('Sign in');
});
