<?php

declare(strict_types=1);

use CraftCms\Cms\Blade\BladeRenderer;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Auth\AuthenticationException;
use Illuminate\View\ViewException;

beforeEach(function () {
    TemplateMode::set(TemplateMode::Site);
    app()->forgetScopedInstances();

    $this->renderer = app(BladeRenderer::class);
});

it('requires a logged-in user', function () {
    try {
        $this->renderer->renderString('@craftRequireLogin');
        throw new RuntimeException('Expected @craftRequireLogin to fail for guests.');
    } catch (ViewException $e) {
        expect($e->getPrevious())->toBeInstanceOf(AuthenticationException::class);
    }
});
