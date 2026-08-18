<?php

declare(strict_types=1);

use CraftCms\Cms\Validation\Concerns\Validates;
use CraftCms\Cms\Validation\Contracts\Validatable;
use CraftCms\Cms\Validation\Rules\EnvValueRule;
use CraftCms\Cms\Validation\ValidatableRules;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EnvValueRuleTestRuleset extends ValidatableRules
{
    #[Override]
    public function rules(): array
    {
        return [
            'email' => new EnvValueRule(['required', 'email']),
        ];
    }
}

function makeEnvValueRuleTestComponent(string $email): Validatable
{
    return new class($email) implements Validatable
    {
        use Validates;

        public function __construct(
            public string $email,
        ) {}

        public function ruleset(): string
        {
            return EnvValueRuleTestRuleset::class;
        }

        public function setAttributes(array $values, bool $safeOnly = true): void
        {
            foreach ($values as $attribute => $value) {
                $this->{$attribute} = $value;
            }
        }
    };
}

function makeEnvValueRuleValidator(mixed $value, array $rules)
{
    return Validator::make([
        'value' => $value,
    ], [
        'value' => [new EnvValueRule($rules)],
    ]);
}

afterEach(function () {
    putenv('ENV_VALUE_RULE_EMAIL');
    putenv('ENV_VALUE_RULE_MAILER');
    putenv('ENV_VALUE_RULE_MISSING');
    putenv('ENV_VALUE_RULE_BOOL');
    putenv('PASSWORD');
});

it('passes validation for resolved values', function (mixed $value, array $rules, ?Closure $setup = null) {
    $setup?->__invoke();

    expect(makeEnvValueRuleValidator($value, $rules)->passes())->toBeTrue();
})->with([
    'literal email' => ['test@example.com', ['required', 'email']],
    'environment variable email' => ['$ENV_VALUE_RULE_EMAIL', ['required', 'email'], fn () => putenv('ENV_VALUE_RULE_EMAIL=test@example.com')],
    'nullable missing environment variable' => ['$ENV_VALUE_RULE_MISSING', ['nullable', 'email']],
]);

it('fails validation for resolved values', function (mixed $value, array $rules, ?Closure $setup = null) {
    $setup?->__invoke();

    $validator = makeEnvValueRuleValidator($value, $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('value'))->toBeTrue();
})->with([
    'invalid environment variable email' => ['$ENV_VALUE_RULE_EMAIL', ['required', 'email'], fn () => putenv('ENV_VALUE_RULE_EMAIL=not-an-email')],
    'required missing environment variable' => ['$ENV_VALUE_RULE_MISSING', ['required', 'email']],
    'required blank value' => ['', ['required', 'email']],
]);

it('supports inner closures', function () {
    putenv('ENV_VALUE_RULE_EMAIL=resolved');

    $validator = Validator::make([
        'value' => '$ENV_VALUE_RULE_EMAIL',
    ], [
        'value' => [new EnvValueRule([
            function (string $attribute, mixed $value, Closure $fail) {
                if ($value !== 'resolved') {
                    $fail('Value was not resolved.');
                }
            },
        ])],
    ]);

    expect($validator->passes())->toBeTrue();
});

it('supports rule objects', function () {
    putenv('ENV_VALUE_RULE_MAILER=smtp');

    $validator = Validator::make([
        'mailer' => '$ENV_VALUE_RULE_MAILER',
    ], [
        'mailer' => [new EnvValueRule(Rule::in(['smtp']))],
    ]);

    expect($validator->passes())->toBeTrue();
});

it('formats parsed values in errors according to sensitivity', function (string $value, string $expected, Closure $setup) {
    $setup();

    $validator = Validator::make([
        'value' => $value,
    ], [
        'value' => [new EnvValueRule([
            fn (string $attribute, mixed $value, Closure $fail) => $fail('Invalid value.'),
        ], showParsedValueInErrors: true)],
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('value'))->toBe($expected);
})->with([
    'non-sensitive environment variable' => ['$ENV_VALUE_RULE_EMAIL', 'Invalid value. (resolved-value)', fn () => putenv('ENV_VALUE_RULE_EMAIL=resolved-value')],
    'sensitive environment variable' => ['$PASSWORD', 'Invalid value.', fn () => putenv('PASSWORD=resolved-value')],
]);

it('parses boolean environment variables for the boolean rule', function (string $envValue, bool $shouldPass) {
    putenv("ENV_VALUE_RULE_BOOL={$envValue}");

    $validator = makeEnvValueRuleValidator('$ENV_VALUE_RULE_BOOL', ['required', 'boolean']);

    expect($validator->passes())->toBe($shouldPass);
})->with([
    'true' => ['true', true],
    'false' => ['false', true],
    'yes' => ['yes', true],
    'no' => ['no', true],
    'on' => ['on', true],
    'off' => ['off', true],
    '1' => ['1', true],
    '0' => ['0', true],
    'maybe' => ['maybe', false],
    'empty' => ['', false],
]);

it('works from rulesets without mutating the subject value', function () {
    putenv('ENV_VALUE_RULE_EMAIL=test@example.com');
    $component = makeEnvValueRuleTestComponent('$ENV_VALUE_RULE_EMAIL');

    expect($component->validate())->toBeTrue()
        ->and($component->email)->toBe('$ENV_VALUE_RULE_EMAIL');
});
