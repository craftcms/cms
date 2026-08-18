<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Twig;
use CraftCms\Cms\View\Events\TemplateRendered;
use CraftCms\Cms\View\Events\TemplateRendering;
use CraftCms\Cms\View\TemplateEngine;
use CraftCms\Cms\View\TemplateManager;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Twig\Extension\EscaperExtension;
use Twig\Sandbox\SecurityNotAllowedMethodError;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/craft-template-renderer-test-'.uniqid();
    File::ensureDirectoryExists($this->tempDir);

    config(['view.paths' => [$this->tempDir]]);
    TemplateMode::set(TemplateMode::Site);

    file_put_contents($this->tempDir.'/test-template.twig', 'Hello from test template');
    file_put_contents($this->tempDir.'/other-template.twig', 'Other template content');
    file_put_contents($this->tempDir.'/greeting.twig', 'Hello, {{ name }}!');

    app()->forgetScopedInstances();

    $this->manager = app(TemplateManager::class);
});

afterEach(function () {
    File::deleteDirectory($this->tempDir);
});

describe('renderString', function () {
    it('renders string templates', function (string $template, array $variables, string $expected) {
        $result = $this->manager->renderTwigString($template, $variables);

        expect($result)->toBe($expected);
    })->with([
        'static string without dynamic tags' => ['Hello, world!', [], 'Hello, world!'],
        'twig variable interpolation' => ['Hello, {{ name }}!', ['name' => 'Craft'], 'Hello, Craft!'],
        'twig expression' => ['{{ 1 + 2 }}', [], '3'],
        'array variables with filter' => ['{{ items|join(", ") }}', ['items' => ['a', 'b', 'c']], 'a, b, c'],
    ]);

    it('short-circuits the null-safe operator instead of erroring', function (string $template, array $variables, string $expected) {
        config(['app.debug' => true]);
        app()->forgetScopedInstances();

        $result = $this->manager->renderTwigString($template, $variables);

        expect($result)->toBe($expected);
    })->with([
        'property access on null' => ['{{ user?.name }}', ['user' => null], ''],
        'method call on null' => ['{{ user?.getName() }}', ['user' => null], ''],
        'chained null-safe access short-circuits at the first null' => ['{{ user?.profile?.name }}', ['user' => null], ''],
        'non-null case still resolves normally' => ['{{ user?.name }}', ['user' => ['name' => 'Bob']], 'Bob'],
    ]);

    it('does not escape HTML by default', function () {
        $result = $this->manager->renderTwigString('{{ html }}', ['html' => '<strong>bold</strong>']);

        expect($result)->toBe('<strong>bold</strong>');
    });

    it('restores the default escaper strategy after rendering', function (bool $escapeHtml) {
        $twig = app(Twig::class)->get();
        $escaper = $twig->getExtension(EscaperExtension::class);
        $originalStrategy = $escaper->getDefaultStrategy('template.html');

        $this->manager->renderTwigString('{{ 1 + 1 }}', escapeHtml: $escapeHtml);

        expect($escaper->getDefaultStrategy('template.html'))->toBe($originalStrategy);
    })->with([
        'escapeHtml disabled' => [false],
        'escapeHtml enabled' => [true],
    ]);
});

describe('renderTemplate', function () {
    it('renders a template file', function () {
        $result = $this->manager->renderTemplate('test-template.twig', renderer: TemplateEngine::Twig);

        expect($result)->toBe('Hello from test template');
    });

    it('renders a template with variables', function () {
        $result = $this->manager->renderTemplate('greeting.twig', ['name' => 'World'], renderer: TemplateEngine::Twig);

        expect($result)->toBe('Hello, World!');
    });

    it('allows the TemplateRendering event to modify the template name', function () {
        Event::listen(TemplateRendering::class, function (TemplateRendering $event) {
            $event->template = 'other-template.twig';
        });

        expect($this->manager->renderTemplate('test-template.twig', renderer: TemplateEngine::Twig))
            ->toBe('Other template content');
    });
});

describe('renderObjectTemplate', function () {
    it('renders object templates', function (string $template, object $object, array $variables, string $expected) {
        $result = $this->manager->renderObjectTemplate($template, $object, $variables);

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
        $result = $this->manager->renderObjectTemplate('{name}', (object) ['name' => '  spaced  ']);

        expect($result)->toBe('spaced');
    });

    it('restores strict variables setting after rendering', function () {
        $twig = app(Twig::class)->get();
        $wasStrict = $twig->isStrictVariables();

        $this->manager->renderObjectTemplate('{foo}', (object) ['foo' => 'bar']);

        expect($twig->isStrictVariables())->toBe($wasStrict);
    });

    it('does not escape output by default', function () {
        $object = (object) ['value' => '<script>alert(1)</script>'];

        $result = $this->manager->renderObjectTemplate('{value}@1', $object);

        expect($result)->toBe('<script>alert(1)</script>@1');
    });

    it('escapes output when an escaper strategy is given', function () {
        // Use a unique template so it doesn't share a compiled template with the unescaped case above.
        $object = (object) ['value' => '<script>alert(1)</script>'];

        $result = $this->manager->renderObjectTemplate('{value}@2', $object, escaperStrategy: 'html');

        expect($result)->toBe('&lt;script&gt;alert(1)&lt;/script&gt;@2');
    });
});

describe('normalizeObjectTemplate', function () {
    it('converts property shorthand to twig output', function (string $template, string $expected) {
        $result = $this->manager->normalizeObjectTemplate($template);

        expect($result)->toBe($expected);
    })->with([
        'simple property' => ['{title}', '{{ (_variables.title ?? object.title) }}'],
        'property with filter' => ['{title|upper}', '{{ (_variables.title ?? object.title)|upper }}'],
        'function call' => ['{clone()}', '{{ clone() }}'],
    ]);

    it('leaves twig tags unchanged', function (string $template) {
        $result = $this->manager->normalizeObjectTemplate($template);

        expect($result)->toBe($template);
    })->with([
        'print tag' => ['{{ someVar }}'],
        'block tag' => ['{% if true %}yes{% endif %}'],
        'verbatim block' => ['{% verbatim %}{title}{% endverbatim %}'],
        'hash literal' => ['{{ {key: "value"} }}'],
        'nested objects' => ['{{ {"outer": {"inner": "value"}} }}'],
    ]);

    it('does not convert object literals', function (string $template) {
        $result = $this->manager->normalizeObjectTemplate($template);

        expect($result)->toBe($template);
    })->with([
        'double-quoted key' => ['{"key": "value"}'],
        'single-quoted key' => ["{  'key': 'value'}"],
    ]);

    it('handles multiple property tags in the same template', function () {
        $result = $this->manager->normalizeObjectTemplate('{title} - {slug}');

        expect($result)->toContain('(_variables.title ?? object.title)')
            ->toContain('(_variables.slug ?? object.slug)');
    });

    it('wraps code in verbatim blocks', function (string $template) {
        $result = $this->manager->normalizeObjectTemplate($template);

        expect($result)->toContain('{% verbatim %}')
            ->toContain('{% endverbatim %}');
    })->with([
        'inline code' => ['`{title}`'],
        'code block' => ['```{title}```'],
    ]);

    it('returns plain text unchanged', function () {
        $result = $this->manager->normalizeObjectTemplate('Just plain text');

        expect($result)->toBe('Just plain text');
    });

    it('converts property tags mixed with twig tags', function () {
        $result = $this->manager->normalizeObjectTemplate('{title} {{ someGlobal }}');

        expect($result)->toContain('(_variables.title ?? object.title)')
            ->toContain('{{ someGlobal }}');
    });
});

describe('sandboxed rendering', function () {
    it('renders sandboxed strings', function () {
        $result = $this->manager->renderSandboxedString('{{ 1 + 1 }}');

        expect($result)->toBe('2');
    });

    it('does not allow Facade calls in sandbox', function () {
        $this->manager->renderSandboxedString('{{ Config.get("app.name") }}');
    })->throws(SecurityNotAllowedMethodError::class);

    it('renders sandboxed templates', function () {
        Event::fake([TemplateRendering::class, TemplateRendered::class]);

        $result = $this->manager->renderSandboxedTemplate('test-template.twig');

        expect($result)->toBe('Hello from test template');

        Event::assertDispatched(TemplateRendering::class);
        Event::assertDispatched(fn (TemplateRendered $event) => $event->rendererName === TemplateEngine::Twig->value);
    });

    it('returns the template as-is when sandbox is enabled and there are no dynamic tags', function () {
        Event::fake([TemplateRendering::class, TemplateRendered::class]);

        $result = $this->manager->renderSandboxedObjectTemplate('hello world', new stdClass);

        expect($result)->toBe('hello world');

        Event::assertNotDispatched(TemplateRendering::class);
        Event::assertNotDispatched(TemplateRendered::class);
    });
});
