<?php

declare(strict_types=1);

use CraftCms\Cms\Component\Validation\Contracts\ValidatableComponentInterface;
use CraftCms\Cms\Component\Validation\Ruleset;
use CraftCms\Cms\Element\Validation\Events\DefineValidationRules;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\MessageBag;

function createTestRuleset(
    ValidatableComponentInterface $component,
    array $rules = [],
    array $scenarios = [],
    array $messages = [],
): Ruleset {
    return new class($component, $rules, $scenarios, $messages) extends Ruleset
    {
        public bool $prepareForValidationCalled = false;

        public ?array $prepareForValidationAttributes = null;

        public function __construct(
            ValidatableComponentInterface $component,
            private readonly array $testRules,
            private readonly array $testScenarios,
            private readonly array $testMessages,
        ) {
            parent::__construct($component);
        }

        public function scenarios(): array
        {
            return $this->testScenarios;
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

function createTestComponent(): ValidatableComponentInterface
{
    return new class implements ValidatableComponentInterface
    {
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
    };
}

describe('inScenarios', function () {
    test('returns true when scenario matches', function () {
        $component = createTestComponent();
        $ruleset = createTestRuleset($component);
        $ruleset->scenario = 'live';

        expect($ruleset->inScenarios('live'))->toBeTrue();
        expect($ruleset->inScenarios('default', 'live', 'essentials'))->toBeTrue();
    });

    test('returns false when scenario does not match', function () {
        $component = createTestComponent();
        $ruleset = createTestRuleset($component);
        $ruleset->scenario = 'live';

        expect($ruleset->inScenarios('default'))->toBeFalse();
        expect($ruleset->inScenarios('default', 'essentials'))->toBeFalse();
    });
});

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

        Event::assertDispatched(fn (\CraftCms\Cms\Element\Validation\Events\DefineValidationRules $event) => $event->component === $component
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
        $component = createTestComponent();
        $ruleset = createTestRuleset(
            $component,
            rules: [
                'title' => ['required'],
                'slug' => ['required'],
                'body' => ['nullable'],
            ],
            scenarios: [
                'essentials' => ['title', 'slug'],
            ],
        );
        $ruleset->scenario = 'essentials';

        $rules = $ruleset->rules();

        expect($rules)->toHaveKey('title');
        expect($rules)->toHaveKey('slug');
        expect($rules)->not->toHaveKey('body');
    });

    test('returns all rules when scenario maps to null', function () {
        $component = createTestComponent();
        $ruleset = createTestRuleset(
            $component,
            rules: [
                'title' => ['required'],
                'slug' => ['required'],
                'body' => ['nullable'],
            ],
            scenarios: [
                'default' => null,
            ],
        );
        $ruleset->scenario = 'default';

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
        $component = createTestComponent();
        $ruleset = createTestRuleset(
            $component,
            rules: [
                'title' => ['required'],
                'email' => ['required', 'email'],
                'password' => ['required', 'min:8'],
            ],
            scenarios: [
                'login' => ['email', 'password'],
                'profile' => ['title', 'email'],
            ],
        );

        $ruleset->scenario = 'login';
        $loginRules = $ruleset->rules();

        expect($loginRules)->toHaveKeys(['email', 'password']);
        expect($loginRules)->not->toHaveKey('title');

        $ruleset->scenario = 'profile';
        $profileRules = $ruleset->rules();

        expect($profileRules)->toHaveKeys(['title', 'email']);
        expect($profileRules)->not->toHaveKey('password');
    });

    test('undefined scenario returns all rules', function () {
        $component = createTestComponent();
        $ruleset = createTestRuleset(
            $component,
            rules: [
                'title' => ['required'],
                'email' => ['required'],
            ],
            scenarios: [
                'specific' => ['title'],
            ],
        );
        $ruleset->scenario = 'undefined-scenario';

        $rules = $ruleset->rules();

        expect($rules)->toHaveKeys(['title', 'email']);
    });
});
