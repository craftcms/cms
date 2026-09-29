<?php

declare(strict_types=1);

use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\TemplateManager;
use Twig\Error\RuntimeError;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->manager = app(TemplateManager::class);
});

describe('requireLogin', function () {
    it('renders normally when user is logged in', function () {
        actingAs(User::find()->one());

        $result = $this->manager->renderString('{% requireLogin %}Protected content');

        expect(trim($result))->toBe('Protected content');
    });

    it('throws when user is a guest', function () {
        $this->manager->renderString('{% requireLogin %}');
    })->throws(RuntimeError::class);
});

describe('requireGuest', function () {
    it('renders normally when user is a guest', function () {
        $result = $this->manager->renderString('{% requireGuest %}Guest content');

        expect(trim($result))->toBe('Guest content');
    });

    it('throws when user is logged in', function () {
        actingAs(User::find()->one());

        $this->manager->renderString('{% requireGuest %}');
    })->throws(RuntimeError::class);
});
