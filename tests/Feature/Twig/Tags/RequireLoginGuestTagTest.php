<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\TemplateRenderer;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Twig\Error\RuntimeError;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->renderer = app(TemplateRenderer::class);

    // Ensure request()->user() delegates to the Auth guard,
    // which is needed by the yii2-adapter's Controller::requireLogin().
    request()->setUserResolver(fn () => Auth::user());
});

describe('requireLogin', function () {
    it('renders normally when user is logged in', function () {
        actingAs(User::find()->one());

        $result = $this->renderer->renderString('{% requireLogin %}Protected content');

        expect(trim($result))->toBe('Protected content');
    });

    it('throws when user is a guest', function () {
        $this->renderer->renderString('{% requireLogin %}');
    })->throws(RuntimeError::class);
});

describe('requireGuest', function () {
    it('renders normally when user is a guest', function () {
        $result = $this->renderer->renderString('{% requireGuest %}Guest content');

        expect(trim($result))->toBe('Guest content');
    });

    it('throws when user is logged in', function () {
        actingAs(User::find()->one());

        $this->renderer->renderString('{% requireGuest %}');
    })->throws(RuntimeError::class);
});
