<?php

declare(strict_types=1);

use CraftCms\Cms\Validation\Concerns\ValidatesWithRuleset;
use CraftCms\Cms\Validation\Contracts\ValidatableWithRuleset;
use CraftCms\Cms\Validation\Ruleset;

function createValidatableComponent(array $attributes, ?string $rulesetClass = null): ValidatableWithRuleset
{
    return new class($attributes, $rulesetClass) implements ValidatableWithRuleset
    {
        use ValidatesWithRuleset;

        private string $rulesetClass;

        public bool $afterValidateCalled = false;

        public function __construct(
            private array $testAttributes,
            ?string $rulesetClass = null,
        ) {
            $this->rulesetClass = $rulesetClass ?? TestRuleset::class;
        }

        public function getRules(): array
        {
            return [];
        }

        public function getMessages(): array
        {
            return [];
        }

        public function setAttributes(array $values, bool $safeOnly = true): void
        {
            $this->testAttributes = array_merge($this->testAttributes, $values);
        }

        public function getAttributes(): array
        {
            return $this->testAttributes;
        }

        public function attributes(): array
        {
            return array_keys($this->testAttributes);
        }

        public function rulesClass(): string
        {
            return $this->rulesetClass;
        }

        public function afterValidate(): void
        {
            $this->afterValidateCalled = true;
        }

        public function attributeLabels(): array
        {
            return [];
        }
    };
}

class TestRuleset extends Ruleset
{
    public bool $prepareForValidationCalled = false;

    public ?array $prepareForValidationAttributes = null;

    #[Override]
    public function messages(): array
    {
        return [
            'title.required' => 'Title is required.',
            'email.email' => 'Invalid email format.',
        ];
    }

    public function prepareForValidation(?array $attributeNames = null): void
    {
        $this->prepareForValidationCalled = true;
        $this->prepareForValidationAttributes = $attributeNames;
    }

    #[Override]
    protected function defineRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
        ];
    }
}

class EmptyRuleset extends Ruleset
{
    #[Override]
    protected function defineRules(): array
    {
        return [];
    }
}

describe('getRuleset', function () {
    test('resolves ruleset via rulesClass method', function () {
        $component = createValidatableComponent(['title' => 'Test']);

        $ruleset = $component->getRuleset();

        expect($ruleset)->toBeInstanceOf(TestRuleset::class);
    });

    test('caches ruleset instance', function () {
        $component = createValidatableComponent(['title' => 'Test']);

        $ruleset1 = $component->getRuleset();
        $ruleset2 = $component->getRuleset();

        expect($ruleset1)->toBe($ruleset2);
    });

    test('throws when no ruleset configured', function () {
        $component = new class implements ValidatableWithRuleset
        {
            use ValidatesWithRuleset;

            public function getRules(): array
            {
                return [];
            }

            public function getMessages(): array
            {
                return [];
            }

            public function setAttributes(array $values, bool $safeOnly = true): void {}

            public function getAttributes(): array
            {
                return [];
            }

            public function attributes(): array
            {
                return [];
            }

            public function rulesClass(): string
            {
                throw new BadMethodCallException('No ruleset configured');
            }

            public function attributeLabels(): array
            {
                return [];
            }
        };

        expect(fn () => $component->getRuleset())->toThrow(BadMethodCallException::class);
    });
});

describe('validate', function () {
    test('returns true when validation passes', function () {
        $component = createValidatableComponent([
            'title' => 'Valid Title',
            'email' => 'test@example.com',
        ]);

        $result = $component->validate();

        expect($result)->toBeTrue();
    });

    test('returns false when validation fails', function () {
        $component = createValidatableComponent([
            'title' => null,
        ]);

        $result = $component->validate();

        expect($result)->toBeFalse();
    });

    test('calls prepareForValidation before validating', function () {
        $component = createValidatableComponent(['title' => 'Test']);

        $component->validate();

        expect($component->getRuleset()->prepareForValidationCalled)->toBeTrue();
    });

    test('passes attribute names to prepareForValidation', function () {
        $component = createValidatableComponent(['title' => 'Test', 'email' => 'test@example.com']);

        $component->validate(['title']);

        expect($component->getRuleset()->prepareForValidationAttributes)->toBe(['title']);
    });

    test('validates only specified attributes', function () {
        $component = createValidatableComponent([
            'title' => null,
            'email' => 'invalid-email',
        ]);

        $component->validate(['email']);

        expect($component->errors()->has('email'))->toBeTrue();
        expect($component->errors()->has('title'))->toBeFalse();
    });

    test('clears errors by default', function () {
        $component = createValidatableComponent(['title' => 'Test']);
        $component->errors()->add('custom', 'Previous error');

        $component->validate();

        expect($component->errors()->has('custom'))->toBeFalse();
    });

    test('preserves errors when clearErrors is false', function () {
        $component = createValidatableComponent(['title' => 'Test']);
        $component->validate();
        $component->errors()->add('custom', 'Previous error');

        $component->validate(['title'], clearErrors: false);

        expect($component->errors()->has('custom'))->toBeTrue();
        expect($component->errors()->get('custom'))->toBe(['Previous error']);
    });

    test('calls afterValidate', function () {
        $component = createValidatableComponent(['title' => 'Test']);

        $component->validate();

        expect($component->afterValidateCalled)->toBeTrue();
    });
});

describe('errors', function () {
    test('returns validation errors', function () {
        $component = createValidatableComponent([
            'title' => null,
            'email' => 'invalid',
        ]);

        $component->validate();

        expect($component->errors()->has('title'))->toBeTrue();
        expect($component->errors()->has('email'))->toBeTrue();
    });

    test('uses custom messages from ruleset', function () {
        $component = createValidatableComponent(['title' => null]);

        $component->validate(['title']);

        expect($component->errors()->first('title'))->toBe('Title is required.');
    });
});

describe('getFirstErrors', function () {
    test('returns first error for each attribute', function () {
        $component = createValidatableComponent([
            'title' => null,
            'email' => 'invalid',
        ]);

        $component->validate();
        $firstErrors = $component->getFirstErrors();

        expect($firstErrors)->toHaveKey('title');
        expect($firstErrors)->toHaveKey('email');
        expect($firstErrors['title'])->toBe('Title is required.');
    });

    test('returns empty array when no errors', function () {
        $component = createValidatableComponent(['title' => 'Valid'], EmptyRuleset::class);

        $component->validate();

        expect($component->getFirstErrors())->toBe([]);
    });
});
