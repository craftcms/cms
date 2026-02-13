<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\View\Events\RegisterCpTemplateRoots;
use CraftCms\Cms\View\Events\RegisterSiteTemplateRoots;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;

it('defaults to site mode for non-cp requests', function () {
    Context::forgetHidden(TemplateMode::class);

    expect(TemplateMode::get())->toBe(TemplateMode::Site);

    // Verify the result was cached in context
    expect(Context::getHidden(TemplateMode::class))->toBe(TemplateMode::Site);
});

it('defaults to cp mode for cp requests', function () {
    Context::forgetHidden(TemplateMode::class);

    $cpTrigger = Cms::config()->cpTrigger;
    $this->get("/{$cpTrigger}/test");

    expect(TemplateMode::get())->toBe(TemplateMode::Cp);
});

it('can set and get the current mode', function () {
    TemplateMode::set(TemplateMode::Cp);

    expect(TemplateMode::get())->toBe(TemplateMode::Cp);

    TemplateMode::set(TemplateMode::Site);

    expect(TemplateMode::get())->toBe(TemplateMode::Site);
});

it('can check the current mode', function () {
    TemplateMode::set(TemplateMode::Cp);

    expect(TemplateMode::is(TemplateMode::Cp))->toBeTrue();
    expect(TemplateMode::is(TemplateMode::Site))->toBeFalse();

    TemplateMode::set(TemplateMode::Site);

    expect(TemplateMode::is(TemplateMode::Site))->toBeTrue();
    expect(TemplateMode::is(TemplateMode::Cp))->toBeFalse();
});

it('can temporarily switch mode with a callback', function () {
    TemplateMode::set(TemplateMode::Site);

    $result = TemplateMode::with(TemplateMode::Cp, function () {
        expect(TemplateMode::get())->toBe(TemplateMode::Cp);

        return 'from-cp';
    });

    expect($result)->toBe('from-cp');
    expect(TemplateMode::get())->toBe(TemplateMode::Site);
});

it('restores original mode even when callback throws', function () {
    TemplateMode::set(TemplateMode::Site);

    try {
        TemplateMode::with(TemplateMode::Cp, function () {
            throw new RuntimeException('test exception');
        });
    } catch (RuntimeException) {
        // expected
    }

    expect(TemplateMode::get())->toBe(TemplateMode::Site);
});

it('returns correct default template extensions', function () {
    expect(TemplateMode::Cp->defaultTemplateExtensions())->toBe(['twig', 'html']);

    expect(TemplateMode::Site->defaultTemplateExtensions())->toBe(Cms::config()->defaultTemplateExtensions);
});

it('returns correct default template extensions for site with custom config', function () {
    Cms::config()->defaultTemplateExtensions = ['twig', 'html', 'txt'];

    expect(TemplateMode::Site->defaultTemplateExtensions())->toBe(['twig', 'html', 'txt']);
});

it('returns correct index template filenames', function () {
    expect(TemplateMode::Cp->indexTemplateFilenames())->toBe(['index']);

    expect(TemplateMode::Site->indexTemplateFilenames())->toBe(Cms::config()->indexTemplateFilenames);
});

it('returns correct index template filenames for site with custom config', function () {
    Cms::config()->indexTemplateFilenames = ['index', 'default'];

    expect(TemplateMode::Site->indexTemplateFilenames())->toBe(['index', 'default']);
});

it('returns correct private template trigger', function () {
    expect(TemplateMode::Cp->privateTemplateTrigger())->toBe('_');

    expect(TemplateMode::Site->privateTemplateTrigger())->toBe(Cms::config()->privateTemplateTrigger);
});

it('returns correct private template trigger for site with custom config', function () {
    Cms::config()->privateTemplateTrigger = '.';

    expect(TemplateMode::Site->privateTemplateTrigger())->toBe('.');
});

it('has the correct backing values', function () {
    expect(TemplateMode::Cp->value)->toBe('cp');
    expect(TemplateMode::Site->value)->toBe('site');
});

it('dispatches the correct event for template roots', function () {
    Event::fake([RegisterCpTemplateRoots::class, RegisterSiteTemplateRoots::class]);

    TemplateMode::Cp->templateRoots();

    Event::assertDispatched(RegisterCpTemplateRoots::class);
    Event::assertNotDispatched(RegisterSiteTemplateRoots::class);
});
