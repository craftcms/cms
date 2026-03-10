<?php

declare(strict_types=1);

it('can log in to the control panel', function () {
    $this->visit('/admin/login')
        ->fill('.login-form [name="username"]', 'craftcms')
        ->fill('.login-form [name="password"]', 'craftcms2018!!')
        ->press('Sign in')
        ->assertPathBeginsWith('/admin');
});
