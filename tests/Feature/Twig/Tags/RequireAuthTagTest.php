<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\TemplateRenderer;
use CraftCms\Cms\User\Elements\User;
use Twig\Error\RuntimeError;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->renderer = app(TemplateRenderer::class);
});

describe('requireAdmin', function () {
    it('renders normally when user is an admin', function () {
        actingAs(User::find()->one());

        $result = $this->renderer->renderString('{% requireAdmin %}Admin content');

        expect(trim($result))->toBe('Admin content');
    });

    it('throws ForbiddenHttpException when user is not an admin', function () {
        $user = \CraftCms\Cms\User\Models\User::factory()->create(['admin' => false])->asElement();
        actingAs($user);

        $this->renderer->renderString('{% requireAdmin %}');
    })->throws(RuntimeError::class);
});

describe('requirePermission', function () {
    it('throws when user lacks the permission', function () {
        $user = \CraftCms\Cms\User\Models\User::factory()->create(['admin' => false])->asElement();
        actingAs($user);

        $this->renderer->renderString('{% requirePermission "editEntries" %}');
    })->throws(RuntimeError::class);

    it('renders normally when user is admin', function () {
        actingAs(User::find()->one());

        $result = $this->renderer->renderString('{% requirePermission "editEntries" %}Allowed');

        expect(trim($result))->toBe('Allowed');
    });
});
