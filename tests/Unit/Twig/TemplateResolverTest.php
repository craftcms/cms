<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\File as Path;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateResolver;
use CraftCms\Cms\View\TemplateRoots;
use Illuminate\Support\Facades\File;
use Twig\Error\LoaderError;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/craft-template-resolver-test-'.uniqid();
    File::ensureDirectoryExists($this->tempDir);

    $this->resolver = new TemplateResolver;

    config(['view.paths' => [$this->tempDir]]);
    TemplateMode::set(TemplateMode::Site);
    Cms::setIsInstalled(false);
});

afterEach(function () {
    File::deleteDirectory($this->tempDir);
});

describe('exists', function () {
    it('returns true when template file exists', function () {
        file_put_contents($this->tempDir.'/my-template.twig', 'hello');

        expect($this->resolver->exists('my-template'))->toBeTrue();
    });

    it('returns false when template does not exist', function () {
        expect($this->resolver->exists('nonexistent'))->toBeFalse();
    });

    it('returns false for templates with NUL bytes', function () {
        expect($this->resolver->exists("bad\0template"))->toBeFalse();
    });

    it('returns false for path traversal attempts', function () {
        expect($this->resolver->exists('../../../etc/passwd'))->toBeFalse();
    });

    it('accepts explicit template mode parameter', function () {
        file_put_contents($this->tempDir.'/site-template.html', 'site content');

        TemplateMode::set(TemplateMode::Cp);

        expect($this->resolver->exists('site-template', TemplateMode::Site))->toBeTrue();
    });

    it('filters private templates when publicOnly is true', function () {
        mkdir($this->tempDir.'/_private', 0777, true);
        file_put_contents($this->tempDir.'/_private/secret.twig', 'secret');

        // publicOnly: true should not find private templates
        expect($this->resolver->exists('_private/secret', publicOnly: true))->toBeFalse();

        // publicOnly: false should find private templates
        // Using a fresh resolver to avoid cache from the previous call
        $resolver = new TemplateResolver;
        expect($resolver->exists('_private/secret', publicOnly: false))->toBeTrue();
    });
});

describe('resolve', function () {
    it('resolves exact file path', function () {
        file_put_contents($this->tempDir.'/exact-file.html', 'content');

        expect($this->resolver->resolve('exact-file.html'))->toBe(Path::normalizePath($this->tempDir.'/exact-file.html'));
    });

    it('resolves template with .twig extension', function () {
        file_put_contents($this->tempDir.'/my-page.twig', 'content');

        expect($this->resolver->resolve('my-page'))->toBe(Path::normalizePath($this->tempDir.'/my-page.twig'));
    });

    it('resolves template with .html extension', function () {
        file_put_contents($this->tempDir.'/my-page.html', 'content');

        expect($this->resolver->resolve('my-page'))->toBe(Path::normalizePath($this->tempDir.'/my-page.html'));
    });

    it('prefers .twig over .html for site mode', function () {
        Cms::config()->defaultTemplateExtensions = ['twig', 'html'];

        file_put_contents($this->tempDir.'/page.twig', 'twig content');
        file_put_contents($this->tempDir.'/page.html', 'html content');

        $result = $this->resolver->resolve('page');

        expect($result)->toBe(Path::normalizePath($this->tempDir.'/page.twig'));
    });

    it('resolves index template in directory', function () {
        mkdir($this->tempDir.'/section', 0777, true);
        file_put_contents($this->tempDir.'/section/index.twig', 'index content');

        expect($this->resolver->resolve('section'))->toBe(Path::normalizePath($this->tempDir.'/section/index.twig'));
    });

    it('resolves index.html template in directory', function () {
        mkdir($this->tempDir.'/section', 0777, true);
        file_put_contents($this->tempDir.'/section/index.html', 'index content');

        expect($this->resolver->resolve('section'))->toBe(Path::normalizePath($this->tempDir.'/section/index.html'));
    });

    it('resolves empty name to index template (homepage)', function () {
        file_put_contents($this->tempDir.'/index.twig', 'homepage');

        expect($this->resolver->resolve(''))->toBe(Path::normalizePath($this->tempDir.'/index.twig'));
    });

    it('returns false when template does not exist', function () {
        expect($this->resolver->resolve('nonexistent'))->toBeFalse();
    });

    it('normalizes backslashes in template names', function () {
        mkdir($this->tempDir.'/sub', 0777, true);
        file_put_contents($this->tempDir.'/sub/page.twig', 'content');

        expect($this->resolver->resolve('sub\\page'))->toBe(Path::normalizePath($this->tempDir.'/sub/page.twig'));
    });

    it('normalizes multiple slashes in template names', function () {
        mkdir($this->tempDir.'/sub', 0777, true);
        file_put_contents($this->tempDir.'/sub/page.twig', 'content');

        expect($this->resolver->resolve('sub///page'))->toBe(Path::normalizePath($this->tempDir.'/sub/page.twig'));
    });

    it('caches resolved paths for the same template', function () {
        file_put_contents($this->tempDir.'/cached.twig', 'content');

        $first = $this->resolver->resolve('cached');
        $second = $this->resolver->resolve('cached');

        expect($first)->toBe($second)
            ->and($first)->toBe(Path::normalizePath($this->tempDir.'/cached.twig'));
    });

    it('strips NUL bytes during name normalization', function () {
        file_put_contents($this->tempDir.'/badtemplate.twig', 'content');

        // NUL bytes are stripped by Str::convertToUtf8, so "bad\0template" resolves as "badtemplate"
        expect($this->resolver->resolve("bad\0template"))->toBe(Path::normalizePath($this->tempDir.'/badtemplate.twig'));
    });

    it('throws LoaderError for path traversal', function () {
        $this->resolver->resolve('../../../etc/passwd');
    })->throws(LoaderError::class, 'outside the template folder');

    it('returns false for private templates when publicOnly is true', function () {
        file_put_contents($this->tempDir.'/_partial.twig', 'private');

        expect($this->resolver->resolve('_partial', publicOnly: true))->toBeFalse();
    });

    it('resolves private templates when publicOnly is false', function () {
        file_put_contents($this->tempDir.'/_partial.twig', 'private');

        expect($this->resolver->resolve('_partial', publicOnly: false))->toBe(Path::normalizePath($this->tempDir.'/_partial.twig'));
    });

    it('uses custom private template trigger from config', function () {
        Cms::config()->privateTemplateTrigger = '.';

        file_put_contents($this->tempDir.'/.hidden.twig', 'hidden');

        expect($this->resolver->resolve('.hidden', publicOnly: true))->toBeFalse();
        expect($this->resolver->resolve('.hidden', publicOnly: false))->toBe(Path::normalizePath($this->tempDir.'/.hidden.twig'));
    });
});

describe('custom template extensions', function () {
    it('resolves templates with custom extensions', function () {
        Cms::config()->defaultTemplateExtensions = ['htm'];

        file_put_contents($this->tempDir.'/page.htm', 'content');

        expect($this->resolver->resolve('page'))->toBe(Path::normalizePath($this->tempDir.'/page.htm'));
    });

    it('does not resolve templates with non-configured extensions', function () {
        Cms::config()->defaultTemplateExtensions = ['htm'];

        file_put_contents($this->tempDir.'/page.twig', 'content');

        // Should not find .twig since only .htm is configured
        // But exact file match still works
        expect($this->resolver->resolve('page'))->toBeFalse();
    });
});

describe('custom index filenames', function () {
    it('resolves custom index filenames', function () {
        Cms::config()->indexTemplateFilenames = ['default'];

        mkdir($this->tempDir.'/section', 0777, true);
        file_put_contents($this->tempDir.'/section/default.twig', 'default content');

        expect($this->resolver->resolve('section'))->toBe(Path::normalizePath($this->tempDir.'/section/default.twig'));
    });

    it('does not resolve standard index when custom filenames are set', function () {
        Cms::config()->indexTemplateFilenames = ['default'];

        mkdir($this->tempDir.'/section', 0777, true);
        file_put_contents($this->tempDir.'/section/index.twig', 'index content');

        // New resolver to avoid cache
        $resolver = new TemplateResolver;

        expect($resolver->resolve('section'))->toBeFalse();
    });
});

describe('template roots', function () {
    it('resolves templates from registered template roots', function () {
        $rootDir = $this->tempDir.'/custom-root';
        mkdir($rootDir, 0777, true);
        file_put_contents($rootDir.'/page.twig', 'root content');

        app(TemplateRoots::class)->register(TemplateMode::Cp, 'myroot', $rootDir);

        TemplateMode::set(TemplateMode::Cp);

        // New resolver to avoid cache
        $resolver = new TemplateResolver;

        expect($resolver->resolve('myroot/page'))->toBe(Path::normalizePath($rootDir.'/page.twig'));
    });

    it('resolves template root with empty prefix', function () {
        $rootDir = $this->tempDir.'/fallback-root';
        mkdir($rootDir, 0777, true);
        file_put_contents($rootDir.'/fallback.twig', 'fallback content');

        app(TemplateRoots::class)->register(TemplateMode::Cp, '', $rootDir);

        TemplateMode::set(TemplateMode::Cp);

        $resolver = new TemplateResolver;

        expect($resolver->resolve('fallback'))->toBe(Path::normalizePath($rootDir.'/fallback.twig'));
    });
});

describe('template mode', function () {
    it('resolves templates in CP mode', function () {
        TemplateMode::set(TemplateMode::Cp);

        $cpTemplatesPath = TemplateMode::Cp->templatesPath();

        // CP templates path should contain actual Craft CP templates
        $resolver = new TemplateResolver;

        // The _layouts directory exists in CP templates
        expect($resolver->exists('_layouts/cp'))->toBeTrue();
    });

    it('resolves templates using the specified template mode', function () {
        file_put_contents($this->tempDir.'/site-only.twig', 'site content');

        TemplateMode::set(TemplateMode::Cp);

        // Should find it in Site mode even though current mode is CP
        expect($this->resolver->resolve('site-only', TemplateMode::Site))->toBe(Path::normalizePath($this->tempDir.'/site-only.twig'));
    });

    it('restores template mode after resolve with explicit mode', function () {
        TemplateMode::set(TemplateMode::Cp);

        file_put_contents($this->tempDir.'/test.twig', 'content');

        $this->resolver->resolve('test', TemplateMode::Site);

        expect(TemplateMode::get())->toBe(TemplateMode::Cp);
    });
});

describe('nested templates', function () {
    it('resolves deeply nested templates', function () {
        mkdir($this->tempDir.'/a/b/c', 0777, true);
        file_put_contents($this->tempDir.'/a/b/c/deep.twig', 'deep content');

        expect($this->resolver->resolve('a/b/c/deep'))->toBe(Path::normalizePath($this->tempDir.'/a/b/c/deep.twig'));
    });

    it('resolves nested index templates', function () {
        mkdir($this->tempDir.'/a/b', 0777, true);
        file_put_contents($this->tempDir.'/a/b/index.twig', 'nested index');

        expect($this->resolver->resolve('a/b'))->toBe(Path::normalizePath($this->tempDir.'/a/b/index.twig'));
    });
});
