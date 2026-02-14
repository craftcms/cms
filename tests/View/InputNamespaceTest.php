<?php

declare(strict_types=1);

use CraftCms\Cms\View\InputNamespace;

beforeEach(function () {
    $this->inputNamespace = app(InputNamespace::class);
});

describe('get and set', function () {
    it('defaults to null', function () {
        expect($this->inputNamespace->get())->toBeNull();
    });

    it('can set and get a namespace', function () {
        $this->inputNamespace->set('foo');

        expect($this->inputNamespace->get())->toBe('foo');
    });

    it('can set namespace to null', function () {
        $this->inputNamespace->set('foo');
        $this->inputNamespace->set(null);

        expect($this->inputNamespace->get())->toBeNull();
    });

    it('returns self from set for chaining', function () {
        $result = $this->inputNamespace->set('foo');

        expect($result)->toBe($this->inputNamespace);
    });
});

describe('with', function () {
    it('temporarily sets the namespace during callback', function () {
        $this->inputNamespace->set('original');

        $this->inputNamespace->with('temporary', function () {
            expect($this->inputNamespace->get())->toBe('temporary');
        });

        expect($this->inputNamespace->get())->toBe('original');
    });

    it('returns the callback result', function () {
        $result = $this->inputNamespace->with('foo', fn () => 'bar');

        expect($result)->toBe('bar');
    });

    it('restores the original namespace even when callback throws', function () {
        $this->inputNamespace->set('original');

        try {
            $this->inputNamespace->with('temporary', function () {
                throw new RuntimeException('test');
            });
        } catch (RuntimeException) {
        }

        expect($this->inputNamespace->get())->toBe('original');
    });

    it('can set a null namespace temporarily', function () {
        $this->inputNamespace->set('original');

        $this->inputNamespace->with(null, function () {
            expect($this->inputNamespace->get())->toBeNull();
        });

        expect($this->inputNamespace->get())->toBe('original');
    });

    it('passes the service instance to the callback', function () {
        $this->inputNamespace->with('foo', function ($instance) {
            expect($instance)->toBe($this->inputNamespace);
        });
    });
});

describe('namespaceInputName', function () {
    it('returns the input name unchanged when no namespace is set or given', function () {
        expect($this->inputNamespace->namespaceInputName('title'))->toBe('title');
    });

    it('returns empty string unchanged', function () {
        $this->inputNamespace->set('foo');

        expect($this->inputNamespace->namespaceInputName(''))->toBe('');
    });

    it('uses the explicit namespace over the active one', function () {
        $this->inputNamespace->set('active');

        expect($this->inputNamespace->namespaceInputName('title', 'explicit'))->toBe('explicit[title]');
    });

    it('falls back to the active namespace', function () {
        $this->inputNamespace->set('foo');

        expect($this->inputNamespace->namespaceInputName('title'))->toBe('foo[title]');
    });

    it('namespaces bracketed names', function (string $expected, string $inputName, string $namespace) {
        expect($this->inputNamespace->namespaceInputName($inputName, $namespace))->toBe($expected);
    })->with([
        'simple name' => ['foo[title]', 'title', 'foo'],
        'nested name' => ['foo[bar][baz]', 'bar[baz]', 'foo'],
        'deeply nested' => ['foo[a][b][c]', 'a[b][c]', 'foo'],
    ]);
});

describe('namespaceInputId', function () {
    it('normalizes the id when no namespace is set or given', function () {
        expect($this->inputNamespace->namespaceInputId('my-field'))->toBe('my-field');
    });

    it('returns empty string unchanged', function () {
        $this->inputNamespace->set('foo');

        expect($this->inputNamespace->namespaceInputId(''))->toBe('');
    });

    it('uses the explicit namespace over the active one', function () {
        $this->inputNamespace->set('active');

        expect($this->inputNamespace->namespaceInputId('title', 'explicit'))->toBe('explicit-title');
    });

    it('falls back to the active namespace', function () {
        $this->inputNamespace->set('foo');

        expect($this->inputNamespace->namespaceInputId('title'))->toBe('foo-title');
    });

    it('normalizes special characters in the id', function () {
        expect($this->inputNamespace->namespaceInputId('foo[bar]', 'ns'))->toBe('ns-foo-bar');
    });
});

describe('namespaceInputs with string html', function () {
    it('returns empty string unchanged', function () {
        $this->inputNamespace->set('foo');

        expect($this->inputNamespace->namespaceInputs(''))->toBe('');
    });

    it('returns html unchanged when no namespace is set or given', function () {
        $html = '<input type="text" name="title" id="title">';

        expect($this->inputNamespace->namespaceInputs($html))->toBe($html);
    });

    it('namespaces html with an explicit namespace', function () {
        $html = '<input type="text" name="title" id="title">';

        $result = $this->inputNamespace->namespaceInputs($html, 'foo');

        expect($result)
            ->toContain('name="foo[title]"')
            ->toContain('id="foo-title"');
    });

    it('falls back to the active namespace', function () {
        $this->inputNamespace->set('foo');
        $html = '<input type="text" name="title" id="title">';

        $result = $this->inputNamespace->namespaceInputs($html);

        expect($result)
            ->toContain('name="foo[title]"')
            ->toContain('id="foo-title"');
    });

    it('only namespaces name attributes when otherAttributes is false', function () {
        $html = '<input type="text" name="title" id="title">';

        $result = $this->inputNamespace->namespaceInputs($html, 'foo', otherAttributes: false);

        expect($result)
            ->toContain('name="foo[title]"')
            ->toContain('id="title"');
    });

    it('namespaces class attributes when withClasses is true', function () {
        $html = '<div class="my-class"><input type="text" name="title"></div>';

        $result = $this->inputNamespace->namespaceInputs($html, 'foo', otherAttributes: true, withClasses: true);

        expect($result)
            ->toContain('name="foo[title]"')
            ->toContain('class="foo-my-class"');
    });

    it('namespaces for and label attributes', function () {
        $html = '<label for="title">Title</label><input type="text" name="title" id="title">';

        $result = $this->inputNamespace->namespaceInputs($html, 'foo');

        expect($result)
            ->toContain('for="foo-title"')
            ->toContain('name="foo[title]"')
            ->toContain('id="foo-title"');
    });
});

describe('namespaceInputs with callable', function () {
    it('returns callable result directly when no explicit namespace is given', function () {
        $this->inputNamespace->set('active');
        $html = '<input type="text" name="title" id="title">';

        $result = $this->inputNamespace->namespaceInputs(fn () => $html);

        expect($result)->toBe($html);
    });

    it('sets the composed namespace before executing the callable', function () {
        $capturedNamespace = null;

        $this->inputNamespace->namespaceInputs(function () use (&$capturedNamespace) {
            $capturedNamespace = $this->inputNamespace->get();

            return '';
        }, 'widget');

        expect($capturedNamespace)->toBe('widget');
    });

    it('restores the original namespace after the callable', function () {
        $this->inputNamespace->set('original');

        $this->inputNamespace->namespaceInputs(fn () => '', 'widget');

        expect($this->inputNamespace->get())->toBe('original');
    });

    it('restores the original namespace when the callable throws', function () {
        $this->inputNamespace->set('original');

        try {
            $this->inputNamespace->namespaceInputs(function () {
                throw new RuntimeException('test');
            }, 'widget');
        } catch (RuntimeException) {
        }

        expect($this->inputNamespace->get())->toBe('original');
    });

    it('namespaces the callable output html', function () {
        $result = $this->inputNamespace->namespaceInputs(
            fn () => '<input type="text" name="title" id="title">',
            'foo',
        );

        expect($result)
            ->toContain('name="foo[title]"')
            ->toContain('id="foo-title"');
    });

    it('composes the explicit namespace with the active namespace for nested callables', function () {
        $this->inputNamespace->set('outer');
        $capturedNamespace = null;

        $this->inputNamespace->namespaceInputs(function () use (&$capturedNamespace) {
            $capturedNamespace = $this->inputNamespace->get();

            return '';
        }, 'inner');

        expect($capturedNamespace)->toBe('outer[inner]');
    });
});

describe('scoped resolution', function () {
    it('is resolved as a scoped instance', function () {
        $a = app(InputNamespace::class);
        $b = app(InputNamespace::class);

        expect($a)->toBe($b);
    });
});
