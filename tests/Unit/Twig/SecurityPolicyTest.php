<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use CraftCms\Cms\Twig\SecurityPolicy;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;

#[AllowedInSandbox]
class AllowedSandboxClassTarget {}

class AllowedSandboxMethodTarget
{
    #[AllowedInSandbox]
    public function allowedMethod(): string
    {
        return 'allowed';
    }

    public function blockedMethod(): string
    {
        return 'blocked';
    }
}

class AllowedSandboxPropertyTarget
{
    #[AllowedInSandbox]
    public string $allowedProperty = 'allowed';

    public string $blockedProperty = 'blocked';
}

beforeEach(function () {
    $this->policy = new SecurityPolicy;
});

describe('AllowedInSandbox attribute', function () {
    it('allows method access when the class is marked with the attribute', function () {
        $target = new AllowedSandboxClassTarget;

        expect(fn () => $this->policy->checkMethodAllowed($target, 'anyMethod'))
            ->not->toThrow(SecurityNotAllowedMethodError::class);
    });

    it('allows property access when the class is marked with the attribute', function () {
        $target = new AllowedSandboxClassTarget;

        expect(fn () => $this->policy->checkPropertyAllowed($target, 'anyProperty'))
            ->not->toThrow(SecurityNotAllowedPropertyError::class);
    });

    it('allows access to a method marked with the attribute', function () {
        $target = new AllowedSandboxMethodTarget;

        expect(fn () => $this->policy->checkMethodAllowed($target, 'allowedMethod'))
            ->not->toThrow(SecurityNotAllowedMethodError::class);
    });

    it('blocks access to methods without the attribute', function () {
        $target = new AllowedSandboxMethodTarget;

        expect(fn () => $this->policy->checkMethodAllowed($target, 'blockedMethod'))
            ->toThrow(SecurityNotAllowedMethodError::class);
    });

    it('allows access to a property marked with the attribute', function () {
        $target = new AllowedSandboxPropertyTarget;

        expect(fn () => $this->policy->checkPropertyAllowed($target, 'allowedProperty'))
            ->not->toThrow(SecurityNotAllowedPropertyError::class);
    });

    it('blocks access to properties without the attribute', function () {
        $target = new AllowedSandboxPropertyTarget;

        expect(fn () => $this->policy->checkPropertyAllowed($target, 'blockedProperty'))
            ->toThrow(SecurityNotAllowedPropertyError::class);
    });
});
