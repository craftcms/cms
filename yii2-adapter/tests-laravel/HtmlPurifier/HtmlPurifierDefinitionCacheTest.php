<?php

declare(strict_types=1);

use craft\helpers\FileHelper;
use craft\helpers\HtmlPurifier;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
use CraftCms\Yii2Adapter\Tests\TestCase;

uses(TestCase::class);

// https://github.com/craftcms/cms/issues/20847

beforeEach(function() {
    $this->basePath = Craft::$app->getPath()->getHtmlPurifierCachePath(false);
    $this->cachePath = $this->basePath . DIRECTORY_SEPARATOR . 'HTML';
    FileHelper::removeDirectory($this->basePath);
});

afterEach(function() {
    if (is_file($this->basePath)) {
        unlink($this->basePath);
    }
    FileHelper::removeDirectory($this->basePath);
});

it('caches the HTML definition for array configs and no config', function() {
    $html = '<figure><a href="https://example.com" download="file.pdf">Link</a><figcaption>Caption</figcaption></figure>';

    // Run each config twice, so the second call is served from the definition cache
    for ($i = 0; $i < 2; $i++) {
        expect(HtmlPurifier::process($html))->toBe($html)
            ->and(HtmlPurifier::process($html, ['HTML.Allowed' => 'a[href]']))->toBe('<a href="https://example.com">Link</a>Caption');
    }

    $cacheFiles = glob($this->cachePath . DIRECTORY_SEPARATOR . '*.ser');
    expect($cacheFiles)->toHaveCount(2)
        ->and(array_diff(scandir($this->cachePath), ['.', '..']))->toHaveCount(2);

    // A cache file that's still being written is treated as a cache miss, and replaced
    foreach ($cacheFiles as $file) {
        $contents = file_get_contents($file);
        file_put_contents($file, substr($contents, 0, intdiv(strlen($contents), 2)));
    }

    expect(HtmlPurifier::process($html))->toBe($html)
        ->and(HtmlPurifier::process($html, ['HTML.Allowed' => 'a[href]']))->toBe('<a href="https://example.com">Link</a>Caption');

    foreach ($cacheFiles as $file) {
        expect(unserialize(file_get_contents($file)))->toBeInstanceOf(HTMLPurifier_HTMLDefinition::class);
    }
});

it('purifies when caching is disabled or the cache directory can’t be created', function() {
    $html = '<figure><figcaption>Caption</figcaption></figure>';
    file_put_contents($this->basePath, '');

    expect(HtmlPurifier::process($html, ['Cache.DefinitionImpl' => null]))->toBe($html)
        ->and(HtmlPurifier::process($html, fn(HTMLPurifier_Config $config) => $config->set('Cache.DefinitionImpl', null)))->toBe($html);
});

it('purifies when caches are cleared while purifying or can’t be cleaned up', function() {
    $html = '<p><mark>Hi</mark></p>';

    expect(HtmlPurifier::process($html, fn() => FileHelper::removeDirectory($this->basePath)))->toBe($html);

    // An old cache entry that can't be removed
    FileHelper::createDirectory($this->cachePath . DIRECTORY_SEPARATOR . '1.0.0,abc,1.ser');

    expect(HtmlPurifier::process($html))->toBe($html);
});

it('caches definitions for configs passed to enableDefinitionCache() by ID', function() {
    $html = '<p data-foo="bar">Hi</p><figure><figcaption>Caption</figcaption></figure>';
    $builds = 0;

    $process = function(string $id, bool $addAttribute) use ($html, &$builds): string {
        $config = HTMLPurifier_Config::createDefault();
        HtmlPurifier::enableDefinitionCache($config, $id);
        // Look up the definition before process(), as callers like CKEditor do
        if ($def = $config->maybeGetRawHTMLDefinition()) {
            $builds++;
            if ($addAttribute) {
                $def->addAttribute('p', 'data-foo', 'Text');
            }
        }

        return HtmlPurifier::process($html, $config);
    };

    // Craft's own customizations (<figure>) must be cached along with the caller's,
    // and a different ID must not get the other config's cached definition
    for ($i = 0; $i < 2; $i++) {
        expect($process('test-with-attr', true))->toBe($html)
            ->and($process('test-without-attr', false))->toBe('<p>Hi</p><figure><figcaption>Caption</figcaption></figure>');
    }

    expect($builds)->toBe(2)
        ->and(glob($this->cachePath . DIRECTORY_SEPARATOR . '*.ser'))->toHaveCount(2);
});

it('lets config objects, closures, and subclasses edit the HTML definition', function() {
    $html = '<p data-foo="bar">Hi</p>';

    $config = HTMLPurifier_Config::createDefault();
    $config->autoFinalize = false;
    $config->set('HTML.DefinitionID', 'my-plugin');
    @$config->getHTMLDefinition(true)->addAttribute('p', 'data-foo', 'Text');
    expect(HtmlPurifier::process($html, $config))->toBe($html);

    for ($i = 0; $i < 2; $i++) {
        expect(HtmlPurifier::process($html, function(HTMLPurifier_Config $config) {
            $config->getHTMLDefinition(true)->addAttribute('p', 'data-foo', 'Text');
        }))->toBe($html);
    }

    $purifier = new class() extends HtmlPurifier {
        public static function configure($config): void
        {
            parent::configure($config);
            $config->getHTMLDefinition(true)->addAttribute('p', 'data-foo', 'Text');
        }
    };
    expect($purifier::process($html))->toBe($html);
});

it('clears the caches from the Caches utility', function() {
    HtmlPurifier::process('<p>Hi</p>');
    $legacyCachePath = Craft::$app->getPath()->getRuntimePath() . DIRECTORY_SEPARATOR . 'HTML';
    FileHelper::createDirectory($legacyCachePath);

    expect(glob($this->cachePath . DIRECTORY_SEPARATOR . '*.ser'))->toHaveCount(1)
        ->and(ClearCaches::clear(['html-purifier']))->toBe(['html-purifier'])
        ->and($this->cachePath)->not->toBeDirectory()
        ->and($this->basePath)->toBeDirectory()
        ->and($legacyCachePath)->not->toBeDirectory();
});
