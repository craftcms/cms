<?php

declare(strict_types=1);

test('can log in to the control panel', function () {
    $this->visit($this->cpUrl('login'))
        ->type('Username or Email', 'admin')
        ->type('Password', 'craftcms2018!!')
        ->press('Sign in')
        ->assertPathEndsWith('dashboard');
});
