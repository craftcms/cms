<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Twig\Twig;
use CraftCms\Cms\Twig\TwigRenderer;
use CraftCms\Cms\View\Events\TemplateRendering;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Twig\Extension\EscaperExtension;
use Twig\Sandbox\SecurityNotAllowedMethodError;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/craft-template-renderer-test-'.uniqid();
    File::ensureDirectoryExists($this->tempDir);

    Aliases::set('@templates', $this->tempDir);
    TemplateMode::set(TemplateMode::Site);

    file_put_contents($this->tempDir.'/test-template.twig', 'Hello from test template');
    file_put_contents($this->tempDir.'/other-template.twig', 'Other template content');
    file_put_contents($this->tempDir.'/greeting.twig', 'Hello, {{ name }}!');

    app()->forgetScopedInstances();

    $this->renderer = app(TwigRenderer::class);
});

afterEach(function () {
    File::deleteDirectory($this->tempDir);
});

describe('renderString', function () {
    it('renders string templates', function (string $template, array $variables, string $expected) {
        $result = $this->renderer->renderString($template, $variables);

        expect($result)->toBe($expected);
    })->with([
        'static string without dynamic tags' => ['Hello, world!', [], 'Hello, world!'],
        'twig variable interpolation' => ['Hello, {{ name }}!', ['name' => 'Craft'], 'Hello, Craft!'],
        'twig expression' => ['{{ 1 + 2 }}', [], '3'],
        'array variables with filter' => ['{{ items|join(", ") }}', ['items' => ['a', 'b', 'c']], 'a, b, c'],
    ]);

    it('does not escape HTML by default', function () {
        $result = $this->renderer->renderString('{{ html }}', ['html' => '<strong>bold</strong>']);

        expect($result)->toBe('<strong>bold</strong>');
    });

    it('restores the default escaper strategy after rendering', function (bool $escapeHtml) {
        $twig = app(Twig::class)->get();

        $this->renderer->renderString('{{ 1 + 1 }}', escapeHtml: $escapeHtml);

        $ext = $twig->getExtension(EscaperExtension::class);
        $strategy = $ext->getDefaultStrategy('template.html');

        expect($strategy)->toBe('html');
    })->with([
        'escapeHtml disabled' => [false],
        'escapeHtml enabled' => [true],
    ]);

    it('restores the template mode after rendering', function () {
        TemplateMode::set(TemplateMode::Site);

        $this->renderer->renderString('{{ 1 }}', templateMode: TemplateMode::Cp);

        expect(TemplateMode::get())->toBe(TemplateMode::Site);
    });

    it('tracks rendering state during render', function () {
        expect($this->renderer->isRenderingTemplate())->toBeFalse();

        $twig = app(Twig::class)->get();
        $twig->addGlobal('_renderer', $this->renderer);

        $result = $this->renderer->renderString(
            '{{ _renderer.isRenderingTemplate ? "yes" : "no" }}',
        );

        expect($result)->toBe('yes');
        expect($this->renderer->isRenderingTemplate())->toBeFalse();
    });
});

describe('renderTemplate', function () {
    it('renders a template file', function () {
        $result = $this->renderer->renderTemplate('test-template.twig');

        expect($result)->toBe('Hello from test template');
    });

    it('renders a template with variables', function () {
        $result = $this->renderer->renderTemplate('greeting.twig', ['name' => 'World']);

        expect($result)->toBe('Hello, World!');
    });

    it('allows the TemplateRendering event to modify the template name', function () {
        Event::listen(TemplateRendering::class, function (TemplateRendering $event) {
            $event->template = 'other-template.twig';
        });

        expect($this->renderer->renderTemplate('test-template.twig'))
            ->toBe('Other template content');
    });
});

describe('renderObjectTemplate', function () {
    it('renders object templates', function (string $template, object $object, array $variables, string $expected) {
        $result = $this->renderer->renderObjectTemplate($template, $object, $variables);

        expect($result)->toBe($expected);
    })->with([
        'static string without dynamic tags' => ['Hello, world!', new stdClass, [], 'Hello, world!'],
        'object property shorthand' => ['{name}', (object) ['name' => 'Craft'], [], 'Craft'],
        'property with surrounding text' => ['Entry: {title}', (object) ['title' => 'My Entry'], [], 'Entry: My Entry'],
        'additional variables' => ['{extra}', new stdClass, ['extra' => 'bonus'], 'bonus'],
        'object via explicit object variable' => ['{{ object.foo }}', (object) ['foo' => 'bar'], [], 'bar'],
        'multiple property shorthand tags' => ['{first} {last}', (object) ['first' => 'John', 'last' => 'Doe'], [], 'John Doe'],
        'additional variables take priority' => ['{name}', (object) ['name' => 'from object'], ['name' => 'from variables'], 'from variables'],
    ]);

    it('trims the output', function () {
        $result = $this->renderer->renderObjectTemplate('{name}', (object) ['name' => '  spaced  ']);

        expect($result)->toBe('spaced');
    });

    it('caches parsed object templates for same template string', function () {
        $object = new stdClass;
        $object->name = 'first';

        $result1 = $this->renderer->renderObjectTemplate('{name}', $object);

        $object->name = 'second';
        $result2 = $this->renderer->renderObjectTemplate('{name}', $object);

        expect($result1)->toBe('first');
        expect($result2)->toBe('second');
    });

    it('restores the template mode after rendering', function () {
        TemplateMode::set(TemplateMode::Site);

        $this->renderer->renderObjectTemplate('{foo}', (object) ['foo' => 'bar'], templateMode: TemplateMode::Cp);

        expect(TemplateMode::get())->toBe(TemplateMode::Site);
    });

    it('restores strict variables setting after rendering', function () {
        $twig = app(Twig::class)->get();
        $wasStrict = $twig->isStrictVariables();

        $this->renderer->renderObjectTemplate('{foo}', (object) ['foo' => 'bar']);

        expect($twig->isStrictVariables())->toBe($wasStrict);
    });
});

describe('normalizeObjectTemplate', function () {
    it('converts property shorthand to twig output', function (string $template, string $expected) {
        $result = $this->renderer->normalizeObjectTemplate($template);

        expect($result)->toBe($expected);
    })->with([
        'simple property' => ['{title}', '{{ (_variables.title ?? object.title)|raw }}'],
        'property with filter' => ['{title|upper}', '{{ (_variables.title ?? object.title)|upper|raw }}'],
        'function call' => ['{clone()}', '{{ clone()|raw }}'],
    ]);

    it('leaves twig tags unchanged', function (string $template) {
        $result = $this->renderer->normalizeObjectTemplate($template);

        expect($result)->toBe($template);
    })->with([
        'print tag' => ['{{ someVar }}'],
        'block tag' => ['{% if true %}yes{% endif %}'],
        'verbatim block' => ['{% verbatim %}{title}{% endverbatim %}'],
        'hash literal' => ['{{ {key: "value"} }}'],
        'nested objects' => ['{{ {"outer": {"inner": "value"}} }}'],
    ]);

    it('does not convert object literals', function (string $template) {
        $result = $this->renderer->normalizeObjectTemplate($template);

        expect($result)->toBe($template);
    })->with([
        'double-quoted key' => ['{"key": "value"}'],
        'single-quoted key' => ["{  'key': 'value'}"],
    ]);

    it('handles multiple property tags in the same template', function () {
        $result = $this->renderer->normalizeObjectTemplate('{title} - {slug}');

        expect($result)->toContain('(_variables.title ?? object.title)')
            ->toContain('(_variables.slug ?? object.slug)');
    });

    it('wraps code in verbatim blocks', function (string $template) {
        $result = $this->renderer->normalizeObjectTemplate($template);

        expect($result)->toContain('{% verbatim %}')
            ->toContain('{% endverbatim %}');
    })->with([
        'inline code' => ['`{title}`'],
        'code block' => ['```{title}```'],
    ]);

    it('returns plain text unchanged', function () {
        $result = $this->renderer->normalizeObjectTemplate('Just plain text');

        expect($result)->toBe('Just plain text');
    });

    it('converts property tags mixed with twig tags', function () {
        $result = $this->renderer->normalizeObjectTemplate('{title} {{ someGlobal }}');

        expect($result)->toContain('(_variables.title ?? object.title)')
            ->toContain('{{ someGlobal }}');
    });
});

describe('sandboxed rendering', function () {
    it('renders sandboxed strings', function () {
        $result = $this->renderer->renderSandboxedString('{{ 1 + 1 }}');

        expect($result)->toBe('2');
    });

    it('does not allow Facade calls in sandbox', function () {
        $this->renderer->renderSandboxedString('{{ Config.get("app.name") }}');
    })->throws(SecurityNotAllowedMethodError::class);

    it('renders sandboxed templates', function () {
        $result = $this->renderer->renderSandboxedTemplate('test-template.twig');

        expect($result)->toBe('Hello from test template');
    });

    it('returns the template as-is when sandbox is enabled and there are no dynamic tags', function () {
        $result = $this->renderer->renderSandboxedObjectTemplate('hello world', new stdClass);

        expect($result)->toBe('hello world');
    });
});
