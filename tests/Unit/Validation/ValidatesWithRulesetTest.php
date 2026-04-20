<?php

declare(strict_types=1);

use CraftCms\Cms\Validation\Concerns\Validates;
use CraftCms\Cms\Validation\Contracts\Validatable;
use CraftCms\Cms\Validation\Ruleset;
use Illuminate\Validation\Validator;

function createValidatableComponent(array $attributes, ?string $rulesetClass = null): Validatable
{
    return new class($attributes, $rulesetClass) implements Validatable
    {
        use Validates;

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

        public function ruleset(): string
        {
            return $this->rulesetClass;
        }

        public function afterValidate(?Validator $validator = null): void
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

    #[Override]
    public function prepareForValidation(): void
    {
        $this->prepareForValidationCalled = true;
        $this->prepareForValidationAttributes = $this->validationAttributes;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
        ];
    }
}

class EmptyRuleset extends Ruleset
{
    public function rules(): array
    {
        return [];
    }
}

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

        expect($component->ruleset->prepareForValidationCalled)->toBeTrue();
    });

    test('passes attribute names to prepareForValidation', function () {
        $component = createValidatableComponent(['title' => 'Test', 'email' => 'test@example.com']);

        $component->validate(['title']);

        expect($component->ruleset->prepareForValidationAttributes)->toBe(['title']);
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
