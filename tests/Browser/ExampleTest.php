<?php

declare(strict_types=1);

it('can load the login page', function () {
    configureBrowserUrls();

    $page = $this->visit('/admin/login');

    $page->assertSee('Username or Email');

    // Verify Vite assets are being served (not stubbed out)
    expect($page->content())->toContain('vendor/craft/build/assets/');
});
