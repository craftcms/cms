<?php

declare(strict_types=1);

use craft\web\twig\CpExtension;
use craft\web\twig\FeExtension;
use craft\web\twig\SinglePreloaderExtension;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Twig\Environment;
use CraftCms\Cms\Twig\Events\TwigCreated;
use CraftCms\Cms\Twig\Extensions\ArrayTwigExtension;
use CraftCms\Cms\Twig\Extensions\CoreTwigExtension;
use CraftCms\Cms\Twig\Extensions\DateTwigExtension;
use CraftCms\Cms\Twig\Extensions\HtmlTwigExtension;
use CraftCms\Cms\Twig\Extensions\TextTwigExtension;
use CraftCms\Cms\Twig\Twig;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\Event;
use Twig\Extension\AbstractExtension;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Extension\StringLoaderExtension;

class StubTwigExtension extends AbstractExtension {}
class AnotherStubTwigExtension extends AbstractExtension {}

beforeEach(function () {
    $this->twig = new Twig;
});

describe('get', function () {
    it('returns an Environment instance', function () {
        expect($this->twig->get())->toBeInstanceOf(Environment::class);
    });

    it('lazily creates the environment on first access', function () {
        Event::fake([TwigCreated::class]);

        Event::assertNotDispatched(TwigCreated::class);

        $this->twig->get();

        Event::assertDispatched(TwigCreated::class);
    });

    it('returns the same cached instance on subsequent calls', function () {
        $first = $this->twig->get();
        $second = $this->twig->get();

        expect($first)->toBe($second);
    });

    it('creates separate environments for Cp and Site modes', function () {
        $cpEnv = $this->twig->get(TemplateMode::Cp);
        $siteEnv = $this->twig->get(TemplateMode::Site);

        expect($cpEnv)->not->toBe($siteEnv);
    });

    it('uses the current template mode when no mode is specified', function () {
        TemplateMode::set(TemplateMode::Site);

        Event::fake([TwigCreated::class]);

        $this->twig->get();

        Event::assertDispatched(fn (TwigCreated $event) => $event->templateMode === TemplateMode::Site);
    });

    it('uses the specified template mode when provided', function () {
        TemplateMode::set(TemplateMode::Cp);

        Event::fake([TwigCreated::class]);

        $this->twig->get(TemplateMode::Site);

        Event::assertDispatched(fn (TwigCreated $event) => $event->templateMode === TemplateMode::Site);
    });

    it('caches Cp and Site environments independently', function () {
        $cpEnv = $this->twig->get(TemplateMode::Cp);
        $siteEnv = $this->twig->get(TemplateMode::Site);

        // Getting the same mode again should return the cached instance
        expect($this->twig->get(TemplateMode::Cp))->toBe($cpEnv);
        expect($this->twig->get(TemplateMode::Site))->toBe($siteEnv);
    });
});

describe('set', function () {
    it('replaces the cached Cp environment', function () {
        $original = $this->twig->get(TemplateMode::Cp);

        TemplateMode::set(TemplateMode::Cp);
        $replacement = $this->twig->create();
        $this->twig->set($replacement);

        expect($this->twig->get(TemplateMode::Cp))->toBe($replacement)
            ->and($this->twig->get(TemplateMode::Cp))->not->toBe($original);
    });

    it('replaces the cached Site environment', function () {
        $original = $this->twig->get(TemplateMode::Site);

        TemplateMode::set(TemplateMode::Site);
        $replacement = $this->twig->create();
        $this->twig->set($replacement);

        expect($this->twig->get(TemplateMode::Site))->toBe($replacement)
            ->and($this->twig->get(TemplateMode::Site))->not->toBe($original);
    });

    it('only replaces the environment for the current template mode', function () {
        $cpEnv = $this->twig->get(TemplateMode::Cp);
        $siteEnv = $this->twig->get(TemplateMode::Site);

        TemplateMode::set(TemplateMode::Cp);
        $replacement = $this->twig->create();
        $this->twig->set($replacement);

        // CP should be replaced, Site should remain
        expect($this->twig->get(TemplateMode::Cp))->toBe($replacement);
        expect($this->twig->get(TemplateMode::Site))->toBe($siteEnv);
    });

    it('replaces the environment for an explicit mode without changing the current mode', function () {
        $original = $this->twig->get(TemplateMode::Site);

        TemplateMode::set(TemplateMode::Cp);
        $replacement = $this->twig->create();
        $this->twig->set($replacement, TemplateMode::Site);

        expect($this->twig->get(TemplateMode::Site))->toBe($replacement)
            ->and($this->twig->get(TemplateMode::Site))->not->toBe($original);
    });
});

describe('create', function () {
    it('returns a new Environment instance', function () {
        $env = $this->twig->create();

        expect($env)->toBeInstanceOf(Environment::class);
    });

    it('returns a different instance each time', function () {
        $first = $this->twig->create();
        $second = $this->twig->create();

        expect($first)->not->toBe($second);
    });

    it('includes the StringLoaderExtension', function () {
        $env = $this->twig->create();

        expect($env->hasExtension(StringLoaderExtension::class))->toBeTrue();
    });

    it('includes the core split extensions', function () {
        $env = $this->twig->create();

        expect($env->hasExtension(CoreTwigExtension::class))->toBeTrue();
        expect($env->hasExtension(DateTwigExtension::class))->toBeTrue();
        expect($env->hasExtension(ArrayTwigExtension::class))->toBeTrue();
        expect($env->hasExtension(TextTwigExtension::class))->toBeTrue();
        expect($env->hasExtension(HtmlTwigExtension::class))->toBeTrue();
    });

    it('includes the CpExtension in Cp mode', function () {
        TemplateMode::set(TemplateMode::Cp);
        $env = $this->twig->create();

        expect($env->hasExtension(CpExtension::class))->toBeTrue();
    });

    it('does not include the CpExtension in Site mode', function () {
        TemplateMode::set(TemplateMode::Site);
        $env = $this->twig->create();

        expect($env->hasExtension(CpExtension::class))->toBeFalse();
    });

    it('includes the FeExtension in Site mode when installed', function () {
        TemplateMode::set(TemplateMode::Site);
        Cms::setIsInstalled(true);
        $env = $this->twig->create();

        expect($env->hasExtension(FeExtension::class))->toBeTrue();
    });

    it('does not include the FeExtension in Site mode when not installed', function () {
        TemplateMode::set(TemplateMode::Site);
        Cms::setIsInstalled(false);
        $env = $this->twig->create();

        expect($env->hasExtension(FeExtension::class))->toBeFalse();
    });

    it('does not include the FeExtension in Cp mode', function () {
        TemplateMode::set(TemplateMode::Cp);
        Cms::setIsInstalled(true);
        $env = $this->twig->create();

        expect($env->hasExtension(FeExtension::class))->toBeFalse();
    });

    it('includes the SinglePreloaderExtension when preloadSingles is enabled and installed', function () {
        TemplateMode::set(TemplateMode::Site);
        Cms::setIsInstalled(true);
        Cms::config()->preloadSingles = true;

        $env = $this->twig->create();

        expect($env->hasExtension(SinglePreloaderExtension::class))->toBeTrue();
    });

    it('does not include the SinglePreloaderExtension when preloadSingles is disabled', function () {
        TemplateMode::set(TemplateMode::Site);
        Cms::setIsInstalled(true);
        Cms::config()->preloadSingles = false;

        $env = $this->twig->create();

        expect($env->hasExtension(SinglePreloaderExtension::class))->toBeFalse();
    });

    it('includes the SandboxExtension', function () {
        $env = $this->twig->create();

        expect($env->hasExtension(SandboxExtension::class))->toBeTrue();
    });

    it('sets the timezone on the CoreExtension', function () {
        $env = $this->twig->create();

        $core = $env->getExtension(CoreExtension::class);

        expect($core->getTimezone()->getName())->toBe(app()->getTimezone());
    });

    it('dispatches the TwigCreated event', function () {
        Event::fake([TwigCreated::class]);

        TemplateMode::set(TemplateMode::Cp);
        $env = $this->twig->create();

        Event::assertDispatched(fn (TwigCreated $event) => $event->twig === $env
            && $event->templateMode === TemplateMode::Cp);
    });

    it('dispatches TwigCreated with the correct template mode', function (TemplateMode $mode) {
        Event::fake([TwigCreated::class]);

        TemplateMode::set($mode);
        $this->twig->create();

        Event::assertDispatched(fn (TwigCreated $event) => $event->templateMode === $mode);
    })->with([
        'Cp mode' => [TemplateMode::Cp],
        'Site mode' => [TemplateMode::Site],
    ]);
});

describe('create options', function () {
    it('enables debug and strict_variables when debug mode is enabled', function () {
        config()->set('app.debug', true);

        $env = (new Twig)->create();

        expect($env->isDebug())->toBeTrue()
            ->and($env->isStrictVariables())->toBeTrue();
    });

    it('does not enable debug options when debug mode is disabled', function () {
        config()->set('app.debug', false);

        $env = (new Twig)->create();

        expect($env->isDebug())->toBeFalse()
            ->and($env->isStrictVariables())->toBeFalse();
    });

    it('caches options across multiple create calls', function () {
        config()->set('app.debug', true);

        $twig = new Twig;
        $first = $twig->create();
        $second = $twig->create();

        // Both should have the same debug setting since options are cached
        expect($first->isDebug())->toBe($second->isDebug());
    });
});

describe('registerExtension', function () {
    it('registers an extension for Cp mode', function () {
        $extension = new StubTwigExtension;
        $this->twig->registerExtension($extension, TemplateMode::Cp);

        $cpEnv = $this->twig->get(TemplateMode::Cp);

        expect($cpEnv->hasExtension(StubTwigExtension::class))->toBeTrue();
    });

    it('registers an extension for Site mode', function () {
        $extension = new StubTwigExtension;
        $this->twig->registerExtension($extension, TemplateMode::Site);

        $siteEnv = $this->twig->get(TemplateMode::Site);

        expect($siteEnv->hasExtension(StubTwigExtension::class))->toBeTrue();
    });

    it('registers an extension for both modes when mode is null', function () {
        $extension = new StubTwigExtension;
        $this->twig->registerExtension($extension);

        $cpEnv = $this->twig->get(TemplateMode::Cp);
        $siteEnv = $this->twig->get(TemplateMode::Site);

        expect($cpEnv->hasExtension(StubTwigExtension::class))->toBeTrue();
        expect($siteEnv->hasExtension(StubTwigExtension::class))->toBeTrue();
    });

    it('adds the extension directly to the cached environment without invalidating it', function () {
        $originalCp = $this->twig->get(TemplateMode::Cp);

        $this->twig->registerExtension(new StubTwigExtension, TemplateMode::Cp);

        expect($this->twig->get(TemplateMode::Cp))->toBe($originalCp)
            ->and($originalCp->hasExtension(StubTwigExtension::class))->toBeTrue();
    });

    it('adds the extension directly to both cached environments when mode is null', function () {
        $originalCp = $this->twig->get(TemplateMode::Cp);
        $originalSite = $this->twig->get(TemplateMode::Site);

        $this->twig->registerExtension(new StubTwigExtension);

        expect($this->twig->get(TemplateMode::Cp))->toBe($originalCp)
            ->and($originalCp->hasExtension(StubTwigExtension::class))->toBeTrue();
        expect($this->twig->get(TemplateMode::Site))->toBe($originalSite)
            ->and($originalSite->hasExtension(StubTwigExtension::class))->toBeTrue();
    });

    it('does not affect the other mode when registering for one mode', function () {
        $cpEnv = $this->twig->get(TemplateMode::Cp);
        $siteEnv = $this->twig->get(TemplateMode::Site);

        $this->twig->registerExtension(new StubTwigExtension, TemplateMode::Cp);

        // Both should remain the same cached instance
        expect($this->twig->get(TemplateMode::Site))->toBe($siteEnv);
        expect($this->twig->get(TemplateMode::Cp))->toBe($cpEnv);
        // Only CP should have the extension
        expect($cpEnv->hasExtension(StubTwigExtension::class))->toBeTrue();
        expect($siteEnv->hasExtension(StubTwigExtension::class))->toBeFalse();
    });

    it('registered extensions are present in newly created environments', function () {
        $extension = new StubTwigExtension;
        $this->twig->registerExtension($extension, TemplateMode::Cp);

        TemplateMode::set(TemplateMode::Cp);

        $env = $this->twig->get();

        expect($env->hasExtension(StubTwigExtension::class))->toBeTrue();
    });

    it('does not add a Cp-registered extension to Site environments', function () {
        $this->twig->registerExtension(new StubTwigExtension, TemplateMode::Cp);

        $siteEnv = $this->twig->get(TemplateMode::Site);

        expect($siteEnv->hasExtension(StubTwigExtension::class))->toBeFalse();
    });

    it('does not add a Site-registered extension to Cp environments', function () {
        $this->twig->registerExtension(new StubTwigExtension, TemplateMode::Site);

        $cpEnv = $this->twig->get(TemplateMode::Cp);

        expect($cpEnv->hasExtension(StubTwigExtension::class))->toBeFalse();
    });

    it('invalidates the cached environment when registering a duplicate extension class', function () {
        $this->twig->registerExtension(new StubTwigExtension, TemplateMode::Cp);
        $originalCp = $this->twig->get(TemplateMode::Cp);

        // Registering the same class again triggers a LogicException from Twig,
        // which causes the cached environment to be invalidated
        $this->twig->registerExtension(new StubTwigExtension, TemplateMode::Cp);

        $newCp = $this->twig->get(TemplateMode::Cp);

        expect($newCp)->not->toBe($originalCp)
            ->and($newCp->hasExtension(StubTwigExtension::class))->toBeTrue();
    });

    it('can register multiple different extensions on the same cached environment', function () {
        $cpEnv = $this->twig->get(TemplateMode::Cp);

        $this->twig->registerExtension(new StubTwigExtension, TemplateMode::Cp);
        $this->twig->registerExtension(new AnotherStubTwigExtension, TemplateMode::Cp);

        // Same instance, both extensions added in-place
        expect($this->twig->get(TemplateMode::Cp))->toBe($cpEnv);
        expect($cpEnv->hasExtension(StubTwigExtension::class))->toBeTrue();
        expect($cpEnv->hasExtension(AnotherStubTwigExtension::class))->toBeTrue();
    });
});
