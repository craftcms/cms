<?php

declare(strict_types=1);

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Support\Template;
use CraftCms\Cms\Twig\NodeVisitors\GetAttrAdjuster;
use CraftCms\Cms\Twig\SecurityPolicy;
use CraftCms\Cms\View\HtmlStack;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Markup;
use Twig\Sandbox\SecurityNotAllowedPropertyError;
use Twig\Source;
use Twig\TwigFunction;

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

    it('reads messages from Laravel message bags with property-style access', function () {
        $errors = new MessageBag(['title' => ['Title is required.']]);

        $value = Template::attribute(
            new Environment(new ArrayLoader),
            new Source('', 'template'),
            $errors,
            'title',
        );

        expect($value)->toBe(['Title is required.']);
    });

    it('checks Laravel message bag keys for defined tests', function () {
        $errors = new MessageBag(['title' => ['Title is required.']]);
        $twig = new Environment(new ArrayLoader);
        $source = new Source('', 'template');

        expect(Template::attribute($twig, $source, $errors, 'title', isDefinedTest: true))->toBeTrue()
            ->and(Template::attribute($twig, $source, $errors, 'handle', isDefinedTest: true))->toBeFalse();
    });

    it('reads default messages from Laravel view error bags with property-style access', function () {
        $errors = new ViewErrorBag;
        $errors->put('default', new MessageBag(['title' => ['Title is required.']]));

        $value = Template::attribute(
            new Environment(new ArrayLoader),
            new Source('', 'template'),
            $errors,
            'title',
        );

        expect($value)->toBe(['Title is required.']);
    });

    it('checks default Laravel view error bag keys for defined tests', function () {
        $errors = new ViewErrorBag;
        $errors->put('default', new MessageBag(['title' => ['Title is required.']]));
        $twig = new Environment(new ArrayLoader);
        $source = new Source('', 'template');

        expect(Template::attribute($twig, $source, $errors, 'title', isDefinedTest: true))->toBeTrue()
            ->and(Template::attribute($twig, $source, $errors, 'handle', isDefinedTest: true))->toBeFalse();
    });

    it('preserves named Laravel view error bag access', function () {
        $errors = new ViewErrorBag;
        $errors->put('login', new MessageBag(['email' => ['Email is required.']]));

        $value = Template::attribute(
            new Environment(new ArrayLoader),
            new Source('', 'template'),
            $errors,
            'login',
        );

        expect($value)->toBeInstanceOf(MessageBag::class)
            ->and($value->get('email'))->toBe(['Email is required.']);
    });

    it('preserves Laravel view error bag method property access', function () {
        $errors = new ViewErrorBag;
        $errors->put('default', new MessageBag(['title' => ['Title is required.']]));

        $value = Template::attribute(
            new Environment(new ArrayLoader),
            new Source('', 'template'),
            $errors,
            'any',
        );

        expect($value)->toBeTrue();
    });
});

describe('Eloquent template attributes', function () {
    beforeEach(function () {
        $this->twig = new Environment(new ArrayLoader);
        $this->twig->addExtension(new class extends AbstractExtension
        {
            public function getNodeVisitors(): array
            {
                return [new GetAttrAdjuster];
            }
        });
        $this->model = new TemplateEloquentTarget;
        $this->model->setRawAttributes(['code' => null, 'getTable' => 'column', 'count' => '7', 'entry' => 'attribute']);
    });

    it('reads attributes through Eloquent rather than invoking methods', function (bool $strict, string $template, string $expected) {
        if ($strict) {
            $this->twig->enableStrictVariables();
        }
        expect($this->twig->createTemplate($template)->render(['model' => $this->model]))->toBe($expected);
    })->with(['strict' => true, 'non-strict' => false])->with([
        'missing dot' => ['{{ model.missing is null ? "null" : "value" }}', 'null'],
        'missing bracket' => ['{{ model["missing"] is null ? "null" : "value" }}', 'null'],
        'null dot' => ['{{ model.code is null ? "null" : "value" }}', 'null'],
        'null bracket' => ['{{ model["code"] is null ? "null" : "value" }}', 'null'],
        'null relation' => ['{{ model.optionalEntry is null ? "null" : "value" }}', 'null'],
        'null relation bracket' => ['{{ model["optionalEntry"] is null ? "null" : "value" }}', 'null'],
        'column before method' => ['{{ model.getTable }}', 'column'],
        'explicit method' => ['{{ model.getTable() }}', 'template_eloquent_targets'],
        'method arguments' => ['{{ model.greet("Craft") }}', 'Hello Craft'],
        'cast' => ['{{ model.count is same as(7) ? "integer" : "other" }}', 'integer'],
        'accessor' => ['{{ model.label }}', 'Accessor'],
        'attribute before relation' => ['{{ model.entry }}', 'attribute'],
        'missing defined' => ['{{ model.missing is defined ? "yes" : "no" }}', 'no'],
        'missing bracket defined' => ['{{ model["missing"] is defined ? "yes" : "no" }}', 'no'],
        'null defined' => ['{{ model.code is defined ? "yes" : "no" }}', 'yes'],
        'null bracket defined' => ['{{ model["code"] is defined ? "yes" : "no" }}', 'yes'],
        'accessor defined' => ['{{ model.label is defined ? "yes" : "no" }}', 'yes'],
        'missing coalesce' => ['{{ model.missing ?? "fallback" }}', 'fallback'],
        'null coalesce' => ['{{ model.code ?? "fallback" }}', 'fallback'],
        'missing bracket coalesce' => ['{{ model["missing"] ?? "fallback" }}', 'fallback'],
        'null relation coalesce' => ['{{ model.optionalEntry ?? "fallback" }}', 'fallback'],
    ]);

    it('resolves unloaded and loaded relations as property values', function (bool $strict, string $expression) {
        if ($strict) {
            $this->twig->enableStrictVariables();
        }
        $this->model->setRawAttributes([]);
        expect($this->model->relationLoaded('entry'))->toBeFalse();

        expect($this->twig->createTemplate('{{ '.$expression.'.title }}')->render(['model' => $this->model]))
            ->toBe('Related');
        expect($this->model->relationLoaded('entry'))->toBeTrue();

        $this->model->setRelation('entry', (new TemplateEloquentTarget)->forceFill(['title' => 'Loaded']));
        expect($this->twig->createTemplate('{{ '.$expression.'.title }}')->render(['model' => $this->model]))
            ->toBe('Loaded');
    })->with(['strict' => true, 'non-strict' => false])->with(['model.entry', 'model["entry"]']);

    it('checks relation existence without loading it', function (bool $strict) {
        if ($strict) {
            $this->twig->enableStrictVariables();
        }
        $this->model->setRawAttributes([]);

        expect($this->twig->createTemplate('{{ model.entry is defined ? "yes" : "no" }}')->render(['model' => $this->model]))
            ->toBe('yes');
        expect($this->model->relationLoaded('entry'))->toBeFalse();
    })->with(['strict' => true, 'non-strict' => false]);

    it('evaluates the model expression once in optimized bracket access', function () {
        $calls = 0;
        $this->twig->addFunction(new TwigFunction('nextModel', function () use (&$calls) {
            $calls++;

            return (new TemplateEloquentTarget)->forceFill(['code' => "value$calls"]);
        }));

        expect($this->twig->createTemplate('{{ nextModel()["code"] }}')->render())->toBe('value1')
            ->and($calls)->toBe(1);
    });

    it('preserves Eloquent missing attribute protection', function (bool $strict, string $expression) {
        if ($strict) {
            $this->twig->enableStrictVariables();
        }
        $this->model->exists = true;
        Model::preventAccessingMissingAttributes();

        try {
            expect(fn () => $this->twig->createTemplate('{{ '.$expression.' }}')->render(['model' => $this->model]))
                ->toThrow(function (RuntimeError $error) {
                    expect($error->getPrevious())->toBeInstanceOf(MissingAttributeException::class);
                });

            expect($this->twig->createTemplate('{{ '.$expression.' ?? "fallback" }}')->render(['model' => $this->model]))
                ->toBe('fallback');
        } finally {
            Model::preventAccessingMissingAttributes(false);
        }
    })->with(['strict' => true, 'non-strict' => false])->with(['model.missing', 'model["missing"]']);

    it('checks property permissions before reading Eloquent attributes', function (bool $strict, string $expression) {
        if ($strict) {
            $this->twig->enableStrictVariables();
        }
        $this->twig->addExtension(new SandboxExtension(new SecurityPolicy(
            allowedFilters: ['escape'],
            allowedProperties: [TemplateEloquentTarget::class => ['label']],
        ), true));

        expect($this->twig->createTemplate('{{ model.label }}')->render(['model' => $this->model]))->toBe('Accessor');
        expect(fn () => $this->twig->createTemplate('{{ '.$expression.' }}')->render(['model' => $this->model]))
            ->toThrow(SecurityNotAllowedPropertyError::class);
    })->with(['strict' => true, 'non-strict' => false])->with(['model.code', 'model["code"]', 'model.entry', 'model["entry"]']);
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

class TemplateAttributeTarget extends Component
{
    public string $title = 'Default';

    public function describeArgument(mixed $argument): string
    {
        return get_debug_type($argument).":$argument";
    }
}

#[Table(name: 'template_test_models')]
class TemplateModelAttributeTarget extends BaseModel {}

class TemplateEloquentTarget extends Model
{
    protected function casts(): array
    {
        return ['count' => 'integer'];
    }

    protected function label(): Attribute
    {
        return Attribute::get(fn () => 'Accessor');
    }

    /**
     * @return BelongsTo<TemplateEloquentTarget, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(self::class)->withDefault(['title' => 'Related']);
    }

    /**
     * @return BelongsTo<TemplateEloquentTarget, $this>
     */
    public function optionalEntry(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }

    public function greet(string $name): string
    {
        return "Hello $name";
    }
}
