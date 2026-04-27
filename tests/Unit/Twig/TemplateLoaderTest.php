<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Twig\Exceptions\TemplateLoaderException;
use CraftCms\Cms\Twig\TemplateLoader;
use CraftCms\Cms\Twig\TemplateResolver;
use CraftCms\Cms\Update\Updates;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\File;
use Twig\Source;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/craft-template-loader-test-'.uniqid();
    File::ensureDirectoryExists($this->tempDir);

    Aliases::set('@templates', $this->tempDir);
    TemplateMode::set(TemplateMode::Site);
    Cms::setIsInstalled(false);

    $this->resolver = new TemplateResolver;
    $this->loader = new TemplateLoader($this->resolver);
});

afterEach(function () {
    File::deleteDirectory($this->tempDir);
});

describe('exists', function () {
    it('returns true when template exists', function () {
        file_put_contents($this->tempDir.'/page.twig', 'content');

        expect($this->loader->exists('page'))->toBeTrue();
    });

    it('returns false when template does not exist', function () {
        expect($this->loader->exists('nonexistent'))->toBeFalse();
    });
});

describe('getSourceContext', function () {
    it('returns a Source object with template content', function () {
        file_put_contents($this->tempDir.'/hello.twig', 'Hello {{ name }}');

        $source = $this->loader->getSourceContext('hello');

        expect($source)->toBeInstanceOf(Source::class)
            ->and($source->getCode())->toBe('Hello {{ name }}')
            ->and($source->getName())->toBe('hello')
            ->and($source->getPath())->toBe($this->tempDir.'/hello.twig');
    });

    it('throws TemplateLoaderException when template does not exist', function () {
        $this->loader->getSourceContext('nonexistent');
    })->throws(TemplateLoaderException::class, 'Unable to find the template');

    it('throws TemplateLoaderException when template is not readable', function () {
        $path = $this->tempDir.'/unreadable.twig';
        file_put_contents($path, 'content');
        chmod($path, 0000);

        $this->loader->getSourceContext('unreadable');
    })->throws(TemplateLoaderException::class, 'could not');

    it('includes the template name in TemplateLoaderException', function () {
        try {
            $this->loader->getSourceContext('missing-template');
        } catch (TemplateLoaderException $e) {
            expect($e->template)->toBe('missing-template');

            return;
        }

        test()->fail('Expected TemplateLoaderException was not thrown');
    });
});

describe('getCacheKey', function () {
    it('returns the resolved template path as cache key', function () {
        file_put_contents($this->tempDir.'/cached.twig', 'content');

        $cacheKey = $this->loader->getCacheKey('cached');

        expect($cacheKey)->toBe($this->tempDir.'/cached.twig');
    });

    it('throws TemplateLoaderException when template does not exist', function () {
        $this->loader->getCacheKey('nonexistent');
    })->throws(TemplateLoaderException::class, 'Unable to find the template');
});

describe('isFresh', function () {
    it('returns true when cached template is newer than source', function () {
        $path = $this->tempDir.'/fresh.twig';
        file_put_contents($path, 'content');

        // Cache time is in the future
        expect($this->loader->isFresh('fresh', time() + 3600))->toBeTrue();
    });

    it('returns false when source is newer than cache', function () {
        $path = $this->tempDir.'/stale.twig';
        file_put_contents($path, 'content');

        // Cache time is far in the past
        expect($this->loader->isFresh('stale', 0))->toBeFalse();
    });

    it('returns false when a craft update is pending on CP request', function () {
        $path = $this->tempDir.'/update-check.twig';
        file_put_contents($path, 'content');

        // Make request()->isCpRequest() return true
        Cms::config()->cpTrigger = '';

        $updates = Mockery::mock(Updates::class);
        $updates->shouldReceive('isCraftUpdatePending')->andReturn(true);
        app()->instance(Updates::class, $updates);

        expect($this->loader->isFresh('update-check', time() + 3600))->toBeFalse();
    });

    it('returns true when no craft update is pending on CP request', function () {
        $path = $this->tempDir.'/no-update.twig';
        file_put_contents($path, 'content');

        // Make request()->isCpRequest() return true
        Cms::config()->cpTrigger = '';

        $updates = Mockery::mock(Updates::class);
        $updates->shouldReceive('isCraftUpdatePending')->andReturn(false);
        app()->instance(Updates::class, $updates);

        expect($this->loader->isFresh('no-update', time() + 3600))->toBeTrue();
    });

    it('does not check for updates on site requests', function () {
        $path = $this->tempDir.'/site-fresh.twig';
        file_put_contents($path, 'content');

        TemplateMode::set(TemplateMode::Site);

        // Even if an update is pending, site requests should not force recompile
        // The Updates mock should NOT be called
        $updates = Mockery::mock(Updates::class);
        $updates->shouldNotReceive('isCraftUpdatePending');
        app()->instance(Updates::class, $updates);

        expect($this->loader->isFresh('site-fresh', time() + 3600))->toBeTrue();
    });

    it('throws TemplateLoaderException when template does not exist', function () {
        $this->loader->isFresh('nonexistent', time());
    })->throws(TemplateLoaderException::class, 'Unable to find the template');
});
