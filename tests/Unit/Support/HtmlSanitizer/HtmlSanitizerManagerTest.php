<?php

declare(strict_types=1);

use CraftCms\Cms\Config\ConfigServiceProvider;
use CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizerManager;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Application;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

beforeEach(function () {
    $this->sanitizers = app(HtmlSanitizerManager::class);
});

test('the default sanitizer preserves Craft additions and is cached', function () {
    $sanitizer = $this->sanitizers->sanitizer();

    expect($sanitizer)
        ->toBeInstanceOf(HtmlSanitizerInterface::class)
        ->toBe($this->sanitizers->driver('default'))
        ->and($this->sanitizers->sanitize('<div data-oembed-url="/video" bad="x"></div><oembed url="/video" bad="x"></oembed>'))
        ->toBe('<div data-oembed-url="/video"></div><oembed url="/video"></oembed>');
});

test('sanitizers can be extended with supported definition forms', function (array|Closure|HtmlSanitizerInterface $definition) {
    $this->sanitizers->extend('paragraphs', $definition);

    expect($this->sanitizers->sanitize('<p class="lead">Hello</p><h1>Heading</h1>', 'paragraphs'))
        ->toBe('<p class="lead">Hello</p>');
})->with([
    'array' => [[
        'allow_elements' => ['p' => ['class']],
    ]],
    'instance' => [new HtmlSanitizer((new HtmlSanitizerConfig)->allowElement('p', ['class']))],
    'closure returning an array' => [fn () => [
        'allow_elements' => ['p' => ['class']],
    ]],
    'closure returning an instance' => [fn () => new HtmlSanitizer((new HtmlSanitizerConfig)->allowElement('p', ['class']))],
]);

test('late replacements take effect after drivers are forgotten', function () {
    $first = new HtmlSanitizer((new HtmlSanitizerConfig)->allowElement('p'));
    $replacement = new HtmlSanitizer((new HtmlSanitizerConfig)->allowElement('h1'));

    $this->sanitizers->extend('custom', $first);

    expect($this->sanitizers->sanitizer('custom'))->toBe($first);

    $this->sanitizers->extend('custom', $replacement);

    expect($this->sanitizers->sanitizer('custom'))->toBe($first);

    $this->sanitizers->forgetDrivers();

    expect($this->sanitizers->sanitizer('custom'))->toBe($replacement);
});

test('creator callbacks use Laravel manager binding and receive the container', function () {
    $manager = $this->sanitizers;
    $callbackManager = null;
    $callbackContainer = null;

    $manager->extend('custom', function (Container $container) use (&$callbackManager, &$callbackContainer) {
        $callbackManager = $this;
        $callbackContainer = $container;

        return new HtmlSanitizer(new HtmlSanitizerConfig);
    });

    $manager->sanitizer('custom');

    expect($callbackManager)->toBe($manager)
        ->and($callbackContainer)->toBe(app());
});

test('default callbacks take effect after drivers are forgotten', function () {
    expect($this->sanitizers->sanitize('<p data-test="yes">Hello</p>'))
        ->toBe('<p>Hello</p>');

    $this->sanitizers->defaults(
        static fn (HtmlSanitizerConfig $config) => $config->allowAttribute('data-test', ['p']),
    );

    expect($this->sanitizers->sanitize('<p data-test="yes">Hello</p>'))
        ->toBe('<p>Hello</p>');

    $this->sanitizers->forgetDrivers();

    expect($this->sanitizers->sanitize('<p data-test="yes">Hello</p>'))
        ->toBe('<p data-test="yes">Hello</p>');
});

test('the default sanitizer can be replaced', function () {
    $replacement = new HtmlSanitizer((new HtmlSanitizerConfig)->allowElement('strong'));

    $this->sanitizers->extend('default', $replacement);

    expect($this->sanitizers->sanitizer())->toBe($replacement);
});

test('invalid definitions are rejected', function () {
    $this->sanitizers->extend('invalid', fn () => new stdClass);

    expect(fn () => $this->sanitizers->sanitizer('invalid'))
        ->toThrow(UnexpectedValueException::class, 'HTML sanitizer [invalid]');
});

test('default callbacks must return a sanitizer config', function () {
    $this->sanitizers->defaults(static fn () => null);

    expect(fn () => $this->sanitizers->defaultConfig())
        ->toThrow(UnexpectedValueException::class, HtmlSanitizerConfig::class);
});

test('names, has, and all expose sanitizers in registration order', function () {
    $resolved = 0;
    $first = new HtmlSanitizer(new HtmlSanitizerConfig);
    $firstReplacement = new HtmlSanitizer(new HtmlSanitizerConfig);
    $second = new HtmlSanitizer(new HtmlSanitizerConfig);

    $this->sanitizers
        ->extend('first', function () use (&$resolved, $first) {
            $resolved++;

            return $first;
        })
        ->extend('second', function () use (&$resolved, $second) {
            $resolved++;

            return $second;
        })
        ->extend('first', function () use (&$resolved, $firstReplacement) {
            $resolved++;

            return $firstReplacement;
        });

    expect($this->sanitizers->has('default'))->toBeTrue()
        ->and($this->sanitizers->has('first'))->toBeTrue()
        ->and($this->sanitizers->has('missing'))->toBeFalse()
        ->and($this->sanitizers->names())->toBe(['default', 'first', 'second'])
        ->and($resolved)->toBe(0);

    $sanitizers = $this->sanitizers->all();

    expect($sanitizers)
        ->keys()->all()->toBe(['default', 'first', 'second'])
        ->and($sanitizers->get('first'))->toBe($firstReplacement)
        ->and($sanitizers->get('second'))->toBe($second)
        ->and($resolved)->toBe(2);
});

test('sanitizer instances can be used directly', function () {
    $sanitizer = new HtmlSanitizer((new HtmlSanitizerConfig)->allowElement('p', ['class']));

    expect($this->sanitizers->sanitize('<p class="lead">Hello</p>', $sanitizer))
        ->toBe('<p class="lead">Hello</p>');
});

test('config repository definitions are registered when the application boots', function () {
    $application = new Application(base_path());
    $application->instance('config', new Repository([
        'app' => ['env' => 'testing'],
        'craft' => [
            'sanitizers' => [
                'default' => ['allow_elements' => ['strong' => []]],
                'paragraphs' => ['allow_elements' => ['p' => []]],
            ],
        ],
    ]));

    $application->register(ConfigServiceProvider::class);
    $application->boot();

    $sanitizers = $application->make(HtmlSanitizerManager::class);

    expect($sanitizers->sanitize('<strong>Default</strong><p>Removed</p>'))
        ->toBe('<strong>Default</strong>')
        ->and($sanitizers->sanitize('<p>Named</p><strong>Removed</strong>', 'paragraphs'))
        ->toBe('<p>Named</p>');
});

test('unknown sanitizers use the Laravel manager exception', function () {
    $this->sanitizers->sanitizer('missing');
})->throws(InvalidArgumentException::class, 'Driver [missing] not supported.');
