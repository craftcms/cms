<?php

declare(strict_types=1);

use CraftCms\Cms\Validation\Concerns\Validates;
use CraftCms\Cms\Validation\Contracts\Validatable;
use CraftCms\Cms\Validation\Ruleset;
use CraftCms\Cms\Validation\ValidatableRules;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

function createValidatableComponent(array $attributes, ?string $rulesetClass = null): Validatable
{
    return new class($attributes, $rulesetClass) implements Validatable
    {
        use Validates;

        private string $rulesetClass;

        public bool $afterValidateCalled = false;

        public bool $prepareForValidationCalled = false;

        public function __construct(
            private array $testAttributes,
            ?string $rulesetClass = null,
        ) {
            $this->rulesetClass = $rulesetClass ?? TestRuleset::class;
        }

        public function setAttributes(array $values, bool $safeOnly = true): void
        {
            $this->testAttributes = array_merge($this->testAttributes, $values);
        }

        public function validationData(): array
        {
            return $this->testAttributes;
        }

        public function ruleset(): string
        {
            return $this->rulesetClass;
        }

        public function prepareForValidation(): void
        {
            $this->prepareForValidationCalled = true;
        }

        public function afterValidate(?Validator $validator = null): void
        {
            $this->afterValidateCalled = true;
        }
    };
}

class TestRuleset extends ValidatableRules
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

    #[Override]
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

class PlainValidatable implements Validatable
{
    use Validates;

    public int $prepareForValidationCalls = 0;

    public int $passedValidationCalls = 0;

    public int $afterValidateCalls = 0;

    public function __construct(
        private array $testAttributes,
        private bool $throwAfterValidation = false,
    ) {}

    public function setAttributes(array $values): void
    {
        $this->testAttributes = array_merge($this->testAttributes, $values);
    }

    public function validationData(): array
    {
        return $this->testAttributes;
    }

    public function getRules(): array
    {
        return [
            'title' => ['required', 'string'],
        ];
    }

    public function getMessages(): array
    {
        return [
            'title.required' => 'Title is required.',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->prepareForValidationCalls++;
    }

    public function passedValidation(): void
    {
        $this->passedValidationCalls++;
    }

    public function afterValidate(?Validator $validator = null): void
    {
        $this->afterValidateCalls++;

        if ($this->throwAfterValidation) {
            throw new RuntimeException('After validation failed.');
        }
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
        expect($component->prepareForValidationCalled)->toBeTrue();
    });

    test('calls subject prepareForValidation from base ruleset', function () {
        $component = createValidatableComponent(['title' => 'Test'], EmptyRuleset::class);

        $component->validate();

        expect($component->prepareForValidationCalled)->toBeTrue();
    });

    test('validates with fallback ruleset when no ruleset is configured', function () {
        $component = new PlainValidatable(['title' => 'Test']);

        $result = $component->validate();

        expect($component->ruleset)->toBeFalse();
        expect($result)->toBeTrue();
        expect($component->prepareForValidationCalls)->toBe(1);
        expect($component->passedValidationCalls)->toBe(1);
        expect($component->afterValidateCalls)->toBe(1);
    });

    test('stores errors from fallback ruleset when no ruleset is configured', function () {
        $component = new PlainValidatable(['title' => null]);

        $result = $component->validate();

        expect($component->ruleset)->toBeFalse();
        expect($result)->toBeFalse();
        expect($component->errors()->has('title'))->toBeTrue();
        expect($component->prepareForValidationCalls)->toBe(1);
        expect($component->passedValidationCalls)->toBe(0);
        expect($component->afterValidateCalls)->toBe(1);
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

    test('runs the validation lifecycle once when throwing validation passes', function () {
        $component = new PlainValidatable(['title' => 'Test']);

        $result = $component->validate(throw: true);

        expect($result)->toBeTrue();
        expect($component->prepareForValidationCalls)->toBe(1);
        expect($component->passedValidationCalls)->toBe(1);
        expect($component->afterValidateCalls)->toBe(1);
    });

    test('runs the validation lifecycle once and preserves errors when throwing validation fails', function () {
        $component = new PlainValidatable(['title' => null]);
        $errors = null;

        try {
            $component->validate(throw: true);
        } catch (ValidationException $caught) {
            $errors = $caught->errors();
        }

        expect($errors)->toBe([
            'title' => ['Title is required.'],
        ]);
        expect($component->prepareForValidationCalls)->toBe(1);
        expect($component->passedValidationCalls)->toBe(0);
        expect($component->afterValidateCalls)->toBe(1);
    });

    test('propagates exceptions from validation hooks after one lifecycle', function () {
        $component = new PlainValidatable(['title' => 'Test'], throwAfterValidation: true);

        expect(fn () => $component->validate(throw: true))
            ->toThrow(RuntimeException::class, 'After validation failed.');
        expect($component->prepareForValidationCalls)->toBe(1);
        expect($component->passedValidationCalls)->toBe(0);
        expect($component->afterValidateCalls)->toBe(1);
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
