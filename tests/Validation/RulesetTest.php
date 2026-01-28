<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Validation\Events\DefineValidationRules;
use CraftCms\Cms\Validation\Concerns\ValidatesWithRuleset;
use CraftCms\Cms\Validation\Contracts\ValidatableWithRuleset;
use CraftCms\Cms\Validation\Ruleset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\MessageBag;

function createTestRuleset(
    ValidatableWithRuleset $component,
    array $rules = [],
    array $messages = [],
): Ruleset {
    return new class($component, $rules, $messages) extends Ruleset
    {
        public bool $prepareForValidationCalled = false;

        public ?array $prepareForValidationAttributes = null;

        public function __construct(
            ValidatableWithRuleset $component,
            private readonly array $testRules,
            private readonly array $testMessages,
        ) {
            parent::__construct($component);
        }

        public function messages(): array
        {
            return $this->testMessages;
        }

        public function prepareForValidation(?array $attributeNames = null): void
        {
            $this->prepareForValidationCalled = true;
            $this->prepareForValidationAttributes = $attributeNames;
        }

        protected function defineRules(): array
        {
            return $this->testRules;
        }
    };
}

function createTestComponent(array $scenarios = []): ValidatableWithRuleset
{
    return new class($scenarios) implements ValidatableWithRuleset
    {
        use ValidatesWithRuleset;

        public function __construct(private array $testScenarios) {}

        public function scenarios(): array
        {
            return $this->testScenarios;
        }

        public static function getRules(): array
        {
            return [];
        }

        public static function getMessages(): array
        {
            return [];
        }

        public function validate(string|array|null $attributeNames = null, bool $clearErrors = true): bool
        {
            return true;
        }

        public function getFirstErrors(): array
        {
            return [];
        }

        public function errors(): MessageBag
        {
            return new MessageBag;
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

        public function attributeLabels(): array
        {
            return [];
        }
    };
}

describe('rules', function () {
    test('returns defined rules', function () {
        $component = createTestComponent();
        $ruleset = createTestRuleset($component, rules: [
            'title' => ['required', 'string'],
            'slug' => ['nullable', 'string'],
        ]);

        $rules = $ruleset->rules();

        expect($rules)->toHaveKey('title');
        expect($rules)->toHaveKey('slug');
        expect($rules['title'])->toBe(['required', 'string']);
    });

    test('fires DefineValidationRules event', function () {
        Event::fake([DefineValidationRules::class]);

        $component = createTestComponent();
        $ruleset = createTestRuleset($component, rules: [
            'title' => ['required'],
        ]);

        $ruleset->rules();

        Event::assertDispatched(fn (DefineValidationRules $event) => $event->component === $component
            && isset($event->rules['title']));
    });

    test('event listeners can modify rules', function () {
        $component = createTestComponent();
        $ruleset = createTestRuleset($component, rules: [
            'title' => ['required'],
        ]);

        Event::listen(DefineValidationRules::class, function (DefineValidationRules $event) {
            $event->addRule('title', 'max:255');
            $event->addRules('email', ['required', 'email']);
        });

        $rules = $ruleset->rules();

        expect($rules['title'])->toContain('max:255');
        expect($rules)->toHaveKey('email');
        expect($rules['email'])->toBe(['required', 'email']);
    });

    test('filters rules by scenario attributes', function () {
        $component = createTestComponent(
            scenarios: [
                'essentials' => ['title', 'slug'],
            ],
        );
        $ruleset = createTestRuleset(
            $component,
            rules: [
                'title' => ['required'],
                'slug' => ['required'],
                'body' => ['nullable'],
            ],
        );
        $component->setScenario('essentials');

        $rules = $ruleset->rules();

        expect($rules)->toHaveKey('title');
        expect($rules)->toHaveKey('slug');
        expect($rules)->not->toHaveKey('body');
    });

    test('returns all rules when scenario maps to null', function () {
        $component = createTestComponent(
            scenarios: [
                'default' => null,
            ],
        );
        $ruleset = createTestRuleset(
            $component,
            rules: [
                'title' => ['required'],
                'slug' => ['required'],
                'body' => ['nullable'],
            ],
        );
        $component->setScenario('default');

        $rules = $ruleset->rules();

        expect($rules)->toHaveKey('title');
        expect($rules)->toHaveKey('slug');
        expect($rules)->toHaveKey('body');
    });

    test('filters out empty rules', function () {
        $component = createTestComponent();
        $ruleset = createTestRuleset($component, rules: [
            'title' => ['required'],
            'slug' => [],
            'body' => null,
        ]);

        $rules = $ruleset->rules();

        expect($rules)->toHaveKey('title');
        expect($rules)->not->toHaveKey('slug');
        expect($rules)->not->toHaveKey('body');
    });
});

describe('prepareForValidation', function () {
    test('is called before validation runs', function () {
        $component = createTestComponent();
        $ruleset = createTestRuleset($component, rules: [
            'title' => ['required'],
        ]);

        expect($ruleset->prepareForValidationCalled)->toBeFalse();

        $ruleset->prepareForValidation(['title']);

        expect($ruleset->prepareForValidationCalled)->toBeTrue();
        expect($ruleset->prepareForValidationAttributes)->toBe(['title']);
    });

    test('receives null when validating all attributes', function () {
        $component = createTestComponent();
        $ruleset = createTestRuleset($component);

        $ruleset->prepareForValidation();

        expect($ruleset->prepareForValidationCalled)->toBeTrue();
        expect($ruleset->prepareForValidationAttributes)->toBeNull();
    });
});

describe('messages', function () {
    test('returns custom messages', function () {
        $component = createTestComponent();
        $ruleset = createTestRuleset($component, messages: [
            'title.required' => 'Please enter a title.',
            'email.email' => 'Invalid email format.',
        ]);

        $messages = $ruleset->messages();

        expect($messages)->toBe([
            'title.required' => 'Please enter a title.',
            'email.email' => 'Invalid email format.',
        ]);
    });
});

describe('scenarios', function () {
    test('scenarios method controls rule filtering', function () {
        $component = createTestComponent(
            scenarios: [
                'login' => ['email', 'password'],
                'profile' => ['title', 'email'],
            ],
        );
        $ruleset = createTestRuleset(
            $component,
            rules: [
                'title' => ['required'],
                'email' => ['required', 'email'],
                'password' => ['required', 'min:8'],
            ],
        );

        $component->setScenario('login');
        $loginRules = $ruleset->rules();

        expect($loginRules)->toHaveKeys(['email', 'password']);
        expect($loginRules)->not->toHaveKey('title');

        $component->setScenario('profile');
        $profileRules = $ruleset->rules();

        expect($profileRules)->toHaveKeys(['title', 'email']);
        expect($profileRules)->not->toHaveKey('password');
    });

    test('undefined scenario returns all rules', function () {
        $component = createTestComponent(
            scenarios: [
                'specific' => ['title'],
            ],
        );
        $ruleset = createTestRuleset(
            $component,
            rules: [
                'title' => ['required'],
                'email' => ['required'],
            ],
        );
        $component->setScenario('undefined-scenario');

        $rules = $ruleset->rules();

        expect($rules)->toHaveKeys(['title', 'email']);
    });
});
