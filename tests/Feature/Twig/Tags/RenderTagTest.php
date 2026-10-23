<?php

declare(strict_types=1);

use CraftCms\Cms\Component\Contracts\Renderable;
use CraftCms\Cms\Support\Template;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\TemplateManager;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Twig\Error\RuntimeError;
use Twig\Sandbox\SecurityNotAllowedMethodError;

/** Renders `$template` with the variables it's given, plus its own `name`. */
function renderable(string $name, string $template = '{{ name }}'): Renderable
{
    return new class($name, $template) implements Renderable
    {
        public function __construct(
            public string $name,
            private readonly string $template,
        ) {}

        public function render(array $variables = []): HtmlString
        {
            return new HtmlString(app(TemplateManager::class)->renderString($this->template, [
                ...$variables,
                'name' => $this->name,
            ]));
        }
    };
}

beforeEach(function () {
    $this->manager = app(TemplateManager::class);
});

describe('{% render %}', function () {
    beforeEach(function () {
        $template = '{{ name }}:{{ foo ?? "-" }}:{{ bar ?? "-" }}';
        [$a, $b, $c] = [renderable('a', $template), renderable('b', $template), renderable('c', $template)];

        $this->vars = [
            'one' => $a,
            'array' => [$a, $b, $c],
            'collection' => Collection::make([$a, $b, $c]),
            'foo' => 'F',
        ];
    });

    it('renders objects with the template context', function (string $template, string $expected) {
        expect($this->manager->renderString($template, $this->vars))->toBe($expected);
    })->with([
        'one' => ['{% render one %}', 'a:F:-'],
        'one with' => ['{% render one with {bar: "B"} %}', 'a:F:B'],
        'one only' => ['{% render one only %}', 'a:-:-'],
        'one with only' => ['{% render one with {bar: "B"} only %}', 'a:-:B'],
        'array' => ['{% render array %}', "a:F:-\nb:F:-\nc:F:-"],
        'array with' => ['{% render array with {bar: "B"} %}', "a:F:B\nb:F:B\nc:F:B"],
        'array only' => ['{% render array only %}', "a:-:-\nb:-:-\nc:-:-"],
        'array with only' => ['{% render array with {bar: "B"} only %}', "a:-:B\nb:-:B\nc:-:B"],
        'array literal' => ['{% render [one, array[1]] %}', "a:F:-\nb:F:-"],
        'empty array' => ['{% render [] %}', ''],
        'collection' => ['{% render collection %}', "a:F:-\nb:F:-\nc:F:-"],
        'collection with' => ['{% render collection with {bar: "B"} %}', "a:F:B\nb:F:B\nc:F:B"],
        'collection only' => ['{% render collection only %}', "a:-:-\nb:-:-\nc:-:-"],
        'collection with only' => ['{% render collection with {bar: "B"} only %}', "a:-:B\nb:-:B\nc:-:B"],
        'collection slice' => ['{% render collection.slice(1) %}', "b:F:-\nc:F:-"],
        'collection render()' => ['{{ collection.render({bar: "B"}) }}', "a:-:B\nb:-:B\nc:-:B"],
        'null' => ['{% render null %}', ''],
    ]);

    it('rejects objects that aren’t renderable', function () {
        $this->manager->renderString('{% render [one, "foo"] %}', $this->vars);
    })->throws(RuntimeError::class, 'string isn’t renderable.');
});

describe('the loop variable', function () {
    beforeEach(function () {
        $template = '{{ name }}:{{ loop.index }}/{{ loop.length }}:{{ loop.index0 }}:{{ loop.revindex }}:{{ loop.revindex0 }}:{{ loop.first ? "first" }}{{ loop.last ? "last" }}:{{ loop.parent.foo ?? "-" }}';
        $this->items = [renderable('a', $template), renderable('b', $template), renderable('c', $template)];
        $this->expected = "a:1/3:0:3:2:first:F\nb:2/3:1:2:1::F\nc:3/3:2:1:0:last:F";
        $this->vars = [
            'array' => $this->items,
            'collection' => Collection::make($this->items),
            'foo' => 'F',
        ];
    });

    it('is given to each of several objects', function (string $template) {
        expect($this->manager->renderString($template, $this->vars))->toBe($this->expected);
    })->with([
        'array' => '{% render array %}',
        'collection' => '{% render collection %}',
        'collection render()' => '{{ collection.render({foo: "F"}) }}',
        'non-sequential keys' => '{% render {x: array[0], y: array[1], z: array[2]} %}',
        'within a for loop' => '{% for obj in array %}{% render obj %}{% if not loop.last %}{{ "\n" }}{% endif %}{% endfor %}',
    ]);

    it('is given to a single item, with the context as its parent unless `only` is used', function () {
        expect($this->manager->renderString('{% render [array[0]] %}', $this->vars))->toBe('a:1/1:0:1:0:firstlast:F')
            ->and($this->manager->renderString('{% render [array[0]] only %}', $this->vars))->toBe('a:1/1:0:1:0:firstlast:-');
    });

    it('nests an outer for loop under `loop.parent.loop`', function () {
        $inner = renderable('i', '{{ name }}{{ loop.index }}/{{ loop.parent.loop.index }}');

        expect($this->manager->renderString(
            '{% for x in 1..2 %}{% render items %}{% if not loop.last %}{{ "\n" }}{% endif %}{% endfor %}',
            ['items' => [$inner, $inner]],
        ))->toBe("i1/1\ni2/1\ni1/2\ni2/2");
    });

    it('leaves a `loop` variable passed via `with` alone', function (string $template) {
        $vars = ['obj' => renderable('a', '{{ name }}:{{ loop }}')];

        expect($this->manager->renderString($template, $vars))->toBe('a:custom');
    })->with([
        'with' => '{% render [obj] with {loop: "custom"} %}',
        'with only' => '{% render [obj] with {loop: "custom"} only %}',
        'within a for loop' => '{% for x in 1..1 %}{% render [obj] with {loop: "custom"} %}{% endfor %}',
    ]);

    it('replaces a `loop` variable in the context', function () {
        expect($this->manager->renderString('{% render [obj] %}', [
            'obj' => renderable('a', '{{ name }}{{ loop.index }}/{{ loop.length }}'),
            'loop' => 'context',
        ]))->toBe('a1/1');
    });

    it('isn’t given to a single object outside a for loop, or with `only`', function () {
        $vars = ['one' => renderable('a', '{{ loop is defined ? "yes" : "no" }}')];

        expect($this->manager->renderString('{% for i in 1..1 %}{% render one %},{% render one only %}{% endfor %}', $vars))->toBe('yes,no')
            ->and($this->manager->renderString('{% render one %}', $vars))->toBe('no');
    });
});

describe('the Twig sandbox', function () {
    beforeEach(function () {
        $this->vars = [
            'one' => renderable('a'),
            'array' => [renderable('a'), renderable('b')],
            'collection' => Collection::make([renderable('a'), renderable('b')]),
            'query' => User::find()->id(-1),
        ];
    });

    it('allows the render tag and render() methods', function (string $template, string $expected) {
        expect($this->manager->renderSandboxedString($template, $this->vars))->toBe($expected);
    })->with([
        'tag with one' => ['{% render one %}', 'a'],
        'tag with an array' => ['{% render array %}', "a\nb"],
        'tag with a collection' => ['{% render collection with {foo: "bar"} only %}', "a\nb"],
        'object render()' => ['{{ one.render() }}', 'a'],
        'collection render()' => ['{{ collection.render() }}', "a\nb"],
        'query render()' => ['{{ query.render() }}', ''],
    ]);

    it('still rejects other collection methods', function () {
        $this->manager->renderSandboxedString('{{ collection.toJson() }}', [
            'collection' => Collection::make([1, 2, 3]),
        ]);
    })->throws(SecurityNotAllowedMethodError::class);
});

it('renders a single object without a loop variable', function () {
    $one = renderable('a', '{{ loop is defined ? "yes" : "no" }}');

    expect((string) Template::render($one))->toBe('no');
});

it('leaves a `loop` variable in the variables alone, but replaces one in the context', function () {
    expect((string) Template::render([renderable('a', '{{ name }}:{{ loop }}')], ['loop' => 'custom']))->toBe('a:custom')
        ->and((string) Template::render(
            [renderable('a', '{{ name }}{{ loop.index }}/{{ loop.length }}')],
            context: ['loop' => 'context'],
        ))->toBe('a1/1');
});
