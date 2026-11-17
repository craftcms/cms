<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use CraftCms\Cms\Twig\SecurityPolicy;
use CraftCms\Cms\Twig\Twig;
use CraftCms\Cms\View\TemplateMode;
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
    $argSets = [[], ['pub'], ['prot'], ['priv'], ['secret'], ['inner'], ['inner.x'], [['inner', 'x']], [[['inner', 'x']]], [null, ['inner']], [[$obj, 'getSecret']], [['SandboxSecretsTarget', 'getSecret']], [$obj]];

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
            foreach ($value as $item) {
                if ($leak = $findLeak($item, $depth + 1)) {
                    return $leak;
                }
            }
        }

        return null;
    };
    $fingerprint = function (mixed $value) use (&$fingerprint): string {
        return match (true) {
            is_object($value) => 'object#'.spl_object_id($value),
            is_array($value) => '['.implode(',', array_map(fn ($k) => "$k:".$fingerprint($value[$k]), array_keys($value))).']',
            default => var_export($value, true),
        };
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

            $call = function (mixed ...$args) use ($callable, $twig): mixed {
                $prefix = array_filter([
                    $callable->needsCharset() ? ['UTF-8'] : null,
                    $callable->needsEnvironment() ? [$twig] : null,
                    $callable->needsContext() ? [[]] : null,
                    $callable->needsIsSandboxed() ? [true] : null,
                ]);

                try {
                    return ($callable->getCallable())(...array_merge(...$prefix), ...$args);
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
