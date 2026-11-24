<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\Template;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use CraftCms\Cms\Twig\SecurityPolicy;
use CraftCms\Cms\Twig\Twig;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Traits\Macroable;
use Ramsey\Uuid\UuidInterface;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;

#[AllowedInSandbox]
class AllowedSandboxClassTarget {}

#[AllowedInSandbox]
class AllowedSandboxMacroTarget
{
    use Macroable;
}

class AllowedSandboxMethodTarget
{
    #[AllowedInSandbox]
    public function allowedMethod(): string
    {
        return 'allowed';
    }

    public function blockedMethod(): string
    {
        return 'blocked';
    }
}

class SandboxSecretsTarget
{
    public string $pub = 'SECRET_PUB';

    protected string $prot = 'SECRET_PROT';

    private string $priv = 'SECRET_PRIV';

    public ArrayObject $inner;

    public function __construct()
    {
        $this->inner = new ArrayObject(['x' => 'SECRET_INNER']);
    }

    public function getSecret(): string
    {
        return 'SECRET_GETTER';
    }

    // Reads $priv without exposing it, so static analysis keeps the property
    public function privLength(): int
    {
        return strlen($this->priv);
    }
}

class AllowedSandboxPropertyTarget
{
    #[AllowedInSandbox]
    public string $allowedProperty = 'allowed';

    public string $blockedProperty = 'blocked';
}

beforeEach(function () {
    $this->policy = new SecurityPolicy;
});

describe('AllowedInSandbox attribute', function () {
    it('allows method access when the class is marked with the attribute', function () {
        $target = new AllowedSandboxClassTarget;

        expect(fn () => $this->policy->checkMethodAllowed($target, 'anyMethod'))
            ->not->toThrow(SecurityNotAllowedMethodError::class);
    });

    it('blocks dynamic macro methods when the class is marked with the attribute', function () {
        AllowedSandboxMacroTarget::macro('dynamicMethod', fn () => 'dynamic');

        try {
            expect(fn () => $this->policy->checkMethodAllowed(new AllowedSandboxMacroTarget, 'dynamicMethod'))
                ->toThrow(SecurityNotAllowedMethodError::class);
        } finally {
            AllowedSandboxMacroTarget::flushMacros();
        }
    });

    it('allows property access when the class is marked with the attribute', function () {
        $target = new AllowedSandboxClassTarget;

        expect(fn () => $this->policy->checkPropertyAllowed($target, 'anyProperty'))
            ->not->toThrow(SecurityNotAllowedPropertyError::class);
    });

    it('allows access to a method marked with the attribute', function () {
        $target = new AllowedSandboxMethodTarget;

        expect(fn () => $this->policy->checkMethodAllowed($target, 'allowedMethod'))
            ->not->toThrow(SecurityNotAllowedMethodError::class);
    });

    it('blocks access to methods without the attribute', function () {
        $target = new AllowedSandboxMethodTarget;

        expect(fn () => $this->policy->checkMethodAllowed($target, 'blockedMethod'))
            ->toThrow(SecurityNotAllowedMethodError::class);
    });

    it('allows access to a property marked with the attribute', function () {
        $target = new AllowedSandboxPropertyTarget;

        expect(fn () => $this->policy->checkPropertyAllowed($target, 'allowedProperty'))
            ->not->toThrow(SecurityNotAllowedPropertyError::class);
    });

    it('blocks access to properties without the attribute', function () {
        $target = new AllowedSandboxPropertyTarget;

        expect(fn () => $this->policy->checkPropertyAllowed($target, 'blockedProperty'))
            ->toThrow(SecurityNotAllowedPropertyError::class);
    });
});

it('rejects historically allowed functions', function (string $function) {
    $this->policy->checkSecurity([], [], [$function]);
})->throws(SecurityNotAllowedFunctionError::class)->with(['parent', 'block', 'attribute']);

it('doesn’t let sandboxed templates replace the content of safe strings', function (object $safe, string $template) {
    expect(fn () => Template::renderSandboxedString($template, ['safe' => $safe]))
        ->toThrow(SecurityNotAllowedMethodError::class);
})->with([
    'Markup constructor' => [new Markup('original', 'UTF-8'), '{% set x = safe.__construct("<script>", "UTF-8") %}{{ safe }}'],
    'Markup::withContent()' => [new Markup('original', 'UTF-8'), '{{ safe.withContent("<script>") }}'],
    'HtmlString constructor' => [new HtmlString('original'), '{% set x = safe.__construct("<script>") %}{{ safe }}'],
]);

/**
 * Calls every filter and function allowed in the sandbox with an object whose properties and methods the sandbox doesn’t
 * allow, and makes sure none of them expose its data: neither by returning it (or an object the template couldn’t
 * access), nor by returning different results depending on it. Filters and functions are covered as soon as they’re
 * added to the sandbox config.
 */
it('doesn’t let allowed filters and functions expose object data the sandbox doesn’t allow', function () {
    $twig = app(Twig::class)->get(TemplateMode::Site);
    $config = config('craft.twig-sandbox');
    $obj = new SandboxSecretsTarget;
    $secrets = ['pub' => 'SECRET_PUB', 'prot' => 'SECRET_PROT', 'priv' => 'SECRET_PRIV', 'secret' => 'SECRET_GETTER'];
    $inputs = [$obj, [$obj], ['k' => $obj], ['data' => ['k' => $obj]]];
    // A variable method name keeps [$obj, $method] a callable array instead of a Closure
    $method = 'getSecret';
    $argSets = [[], ['pub'], ['prot'], ['priv'], ['secret'], ['inner'], ['inner.x'], [['inner', 'x']], [[['inner', 'x']]], [null, ['inner']], [[$obj, $method]], [['SandboxSecretsTarget', 'getSecret']], [$obj]];

    // Returns a description of any secret or new object within the result
    $findLeak = function (mixed $value, int $depth = 0) use (&$findLeak, $obj): ?string {
        if (is_string($value)) {
            return str_contains($value, 'SECRET_') ? $value : null;
        }

        if (is_object($value) && $value !== $obj) {
            if ($value instanceof Markup) {
                return $findLeak((string) $value);
            }

            if (! $value instanceof Traversable) {
                return $value instanceof DateTimeInterface || $value instanceof UuidInterface ? null : $value::class;
            }
        }

        if (is_iterable($value) && $depth < 5) {
            foreach ($value as $k => $item) {
                if ($leak = $findLeak($k) ?? $findLeak($item, $depth + 1)) {
                    return $leak;
                }
            }
        }

        return null;
    };
    $fingerprint = function (mixed $value) use (&$fingerprint): string {
        if (is_array($value)) {
            return '['.implode(',', array_map(fn ($k) => "$k:".$fingerprint($value[$k]), array_keys($value))).']';
        }

        return is_object($value) ? 'object#'.spl_object_id($value) : var_export($value, true);
    };

    $failures = [];
    $checker = $twig->getExtension(SandboxExtension::class)->getChecker();
    $checker->setSandboxed(true);

    try {
        $callables = [
            ...array_map(fn ($name) => ['filter', $name, $twig->getFilter($name)], array_unique($config['allowedFilters'])),
            ...array_map(fn ($name) => ['function', $name, $twig->getFunction($name)], array_unique($config['allowedFunctions'])),
        ];

        foreach ($callables as [$type, $name, $callable]) {
            if (! $callable) {
                continue;
            }

            $fn = $callable->getCallable();

            if (is_array($fn) && is_string($fn[0]) && ! new ReflectionMethod($fn[0], $fn[1])->isStatic()) {
                // Twig calls non-static callbacks on the class’s runtime, e.g. EscaperRuntime::escape()
                $fn = [$twig->getRuntime($fn[0]), $fn[1]];
            }

            $call = function (mixed ...$args) use ($callable, $fn, $twig): mixed {
                $prefix = array_filter([
                    $callable->needsCharset() ? ['UTF-8'] : null,
                    $callable->needsEnvironment() ? [$twig] : null,
                    $callable->needsContext() ? [[]] : null,
                    $callable->needsIsSandboxed() ? [true] : null,
                ]);

                try {
                    return $fn(...array_merge(...$prefix), ...$args);
                } catch (Throwable) {
                    return 'error';
                }
            };

            foreach ($inputs as $i => $input) {
                foreach ($argSets as $j => $args) {
                    if ($leak = $findLeak($call($input, ...$args))) {
                        $failures["$type $name"] ??= "input $i, args $j: $leak";
                    }
                }

                // The result must not depend on whether a value matches a secret
                foreach ($secrets as $key => $secret) {
                    [$a, $b, $c] = array_map(fn ($value) => $fingerprint($call($input, $key, $value)), [$secret, 'SECRET_X', 'SECRET_Y']);

                    if ($b === $c && $a !== $b) {
                        $failures["$type $name"] ??= "input $i: reveals whether \"$key\" matches";
                    }
                }
            }
        }
    } finally {
        $checker->setSandboxed(false);
    }

    expect($failures)->toBe([]);
});
