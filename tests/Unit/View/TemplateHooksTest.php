<?php

declare(strict_types=1);

use CraftCms\Cms\View\TemplateHooks;

beforeEach(function () {
    $this->hooks = app(TemplateHooks::class);
});

describe('scoped resolution', function () {
    it('is resolved as a scoped instance', function () {
        $a = app(TemplateHooks::class);
        $b = app(TemplateHooks::class);

        expect($a)->toBe($b);
    });
});

describe('hook registration', function () {
    it('registers and invokes a hook handler', function () {
        $this->hooks->register('test', fn (array &$context) => 'hello');

        $ctx = [];
        expect($this->hooks->invoke('test', $ctx))->toBe('hello');
    });

    it('concatenates output from multiple handlers', function () {
        $this->hooks->register('test', fn () => 'a');
        $this->hooks->register('test', fn () => 'b');

        $ctx = [];
        expect($this->hooks->invoke('test', $ctx))->toBe('ab');
    });

    it('prepends handlers when append is false', function () {
        $this->hooks->register('test', fn () => 'second');
        $this->hooks->register('test', fn () => 'first', append: false);

        $ctx = [];
        expect($this->hooks->invoke('test', $ctx))->toBe('firstsecond');
    });
});

describe('hook invocation', function () {
    it('returns empty string for unregistered hooks', function () {
        $ctx = [];
        expect($this->hooks->invoke('nonexistent', $ctx))->toBe('');
    });

    it('short-circuits when handler sets handled to true', function () {
        $this->hooks->register('test', function (array &$context, bool &$handled) {
            $handled = true;

            return 'only-this';
        });
        $this->hooks->register('test', fn () => 'never-reached');

        $ctx = [];
        expect($this->hooks->invoke('test', $ctx))->toBe('only-this');
    });

    it('passes context by reference to handlers', function () {
        $this->hooks->register('test', function (array &$context) {
            $context['injected'] = true;

            return '';
        });

        $ctx = [];
        $this->hooks->invoke('test', $ctx);
        expect($ctx['injected'])->toBeTrue();
    });

    it('allows handlers to read context set by previous handlers', function () {
        $this->hooks->register('test', function (array &$context) {
            $context['value'] = 'from-first';

            return '';
        });
        $this->hooks->register('test', fn (array &$context) => $context['value'] ?? 'missing');

        $ctx = [];
        expect($this->hooks->invoke('test', $ctx))->toBe('from-first');
    });
});
