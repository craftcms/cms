<?php

declare(strict_types=1);

use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Support\Template;
use CraftCms\Cms\View\HtmlStack;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Loader\ArrayLoader;
use Twig\Markup;
use Twig\Source;
use yii\base\BaseObject;

beforeEach(function () {
    TestTemplate::resetFallbacks();
});

describe('resolveVariable', function () {
    it('returns values from the context before fallbacks', function () {
        TestTemplate::setFallbacks(['title' => 'Fallback title']);

        expect(TestTemplate::resolveVariable('title', ['title' => 'Context title'], strict: true))
            ->toBe('Context title');
    });

    it('returns fallback values when the context does not contain the variable', function () {
        TestTemplate::setFallbacks(['title' => 'Fallback title']);

        expect(TestTemplate::resolveVariable('title', [], strict: true))
            ->toBe('Fallback title');
    });

    it('returns null for missing variables when strict mode is disabled', function () {
        expect(TestTemplate::resolveVariable('missing', [], strict: false))
            ->toBeNull();
    });

    it('throws for missing variables when strict mode is enabled', function () {
        TestTemplate::resolveVariable('missing', [], strict: true, lineno: 7, source: new Source('', 'template'));
    })->throws(RuntimeError::class, 'Variable "missing" does not exist in "template" at line 7.');
});

describe('variableExists', function () {
    it('returns true for context values and fallback values', function () {
        TestTemplate::setFallbacks(['summary' => 'Fallback summary']);

        expect(TestTemplate::variableExists('title', ['title' => 'Context title']))->toBeTrue()
            ->and(TestTemplate::variableExists('summary', []))->toBeTrue()
            ->and(TestTemplate::variableExists('missing', []))->toBeFalse();
    });
});

describe('raw', function () {
    it('returns Twig markup with a UTF-8 charset', function () {
        $markup = Template::raw('<strong>Hi</strong>');
        $charsetProperty = new ReflectionProperty(Markup::class, 'charset');

        expect($markup)->toBeInstanceOf(Markup::class)
            ->and((string) $markup)->toBe('<strong>Hi</strong>')
            ->and($charsetProperty->getValue($markup))->toBe('UTF-8');
    });
});

describe('attribute', function () {
    it('reads BaseObject properties directly', function () {
        $value = Template::attribute(
            new Environment(new ArrayLoader),
            new Source('', 'template'),
            new TemplateAttributeTarget(['title' => 'Hello']),
            'title',
        );

        expect($value)->toBe('Hello');
    });

    it('reads BaseModel attributes directly', function () {
        $value = Template::attribute(
            new Environment(new ArrayLoader),
            new Source('', 'template'),
            new TemplateModelAttributeTarget(['title' => 'Hello from model']),
            'title',
        );

        expect($value)->toBe('Hello from model');
    });

    it('converts exact Twig markup arguments back to strings for method calls', function () {
        $value = Template::attribute(
            new Environment(new ArrayLoader),
            new Source('', 'template'),
            new TemplateAttributeTarget,
            'describeArgument',
            [new Markup('Craft', 'UTF-8')],
        );

        expect($value)->toBe('string:Craft');
    });
});

describe('contextWithoutTemplate', function () {
    it('filters out Twig template objects from the context', function () {
        $twig = new Environment(new ArrayLoader);
        $wrapper = $twig->createTemplate('Hello');

        expect(Template::contextWithoutTemplate([
            'template' => $wrapper->unwrap(),
            'wrapper' => $wrapper,
            'value' => 'kept',
        ]))->toBe([
            'value' => 'kept',
        ]);
    });
});

describe('js', function () {
    it('defaults to the body-end position when no position option is provided', function () {
        $htmlStack = app(HtmlStack::class);
        $htmlStack->clear();

        Template::js('var x = 1');

        expect($htmlStack->bodyEndHtml())->toContain('var x = 1;');
    });
});

class TestTemplate extends Template
{
    public static function setFallbacks(array $fallbacks): void
    {
        self::$_fallbacks = $fallbacks;
    }

    public static function resetFallbacks(): void
    {
        self::$_fallbacks = [];
    }
}

class TemplateAttributeTarget extends BaseObject
{
    public string $title = 'Default';

    public function describeArgument(mixed $argument): string
    {
        return get_debug_type($argument).":$argument";
    }
}

class TemplateModelAttributeTarget extends BaseModel
{
    #[Override]
    protected $table = 'template_test_models';
}
