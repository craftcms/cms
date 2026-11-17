<?php

use craft\web\twig\variables\CraftVariable as LegacyCraftVariable;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Twig\Variables\Cp;
use CraftCms\Cms\Twig\Variables\CraftVariable as LaravelCraftVariable;
use CraftCms\Cms\View\TemplateManager;
use yii\base\Behavior;
use yii\base\Event;

afterEach(function() {
    Event::off(LegacyCraftVariable::class, LegacyCraftVariable::EVENT_INIT);

    $macros = new ReflectionProperty(LaravelCraftVariable::class, 'macros');
    $values = $macros->getValue();
    unset(
        $values['testVariable'],
        $values['testBehaviorValue'],
        $values['testBehaviorQuery'],
        $values['getTestBehaviorQuery'],
        $values['getTestBehaviorGetter'],
        $values['testBehaviorGetter'],
        $values['testBehaviorMethod'],
        $values['testBehaviorRequired'],
        $values['testBehaviorShared'],
        $values['getTestBehaviorShared'],
    );
    $macros->setValue(null, $values);
});

it('registers custom Twig variables from the legacy init event', function() {
    Event::on(LegacyCraftVariable::class, LegacyCraftVariable::EVENT_INIT, function(Event $event) {
        $event->sender->set('testVariable', new class() {
            public string $value = 'registered';
        });
    });

    expect(app(TemplateManager::class)->renderString('{{ craft.testVariable.value }}'))->toBe('registered');
});

it('exposes behaviors attached from the legacy init event', function() {
    Event::on(LegacyCraftVariable::class, LegacyCraftVariable::EVENT_INIT, function(Event $event) {
        $event->sender->attachBehavior('test', new class() extends Behavior {
            public string $testBehaviorValue = 'property';

            public string $testBehaviorShared = 'first';

            public function testBehaviorQuery(array $criteria = []): string
            {
                return 'query:' . implode(',', $criteria);
            }

            public function getTestBehaviorGetter(): string
            {
                return 'getter';
            }
        });
        $event->sender->attachBehavior('test2', new class() extends Behavior {
            public function getTestBehaviorQuery(): string
            {
                return 'getter-query';
            }

            public function getTestBehaviorShared(): string
            {
                return 'second';
            }
        });
    });

    expect(app(TemplateManager::class)->renderString(
        '{{ craft.testBehaviorValue }}|{{ craft.testBehaviorQuery }}|{{ craft.testBehaviorQuery(["a", "b"]) }}|{{ craft.testBehaviorGetter }}|{{ craft.testBehaviorShared }}',
    ))->toBe('property|query:|query:a,b|getter|first');
});

it('lets behaviors reach the core craft variable through their owner', function() {
    $inits = 0;

    Event::on(LegacyCraftVariable::class, LegacyCraftVariable::EVENT_INIT, function(Event $event) use (&$inits) {
        $inits++;
        $event->sender->attachBehavior('test', new class() extends Behavior {
            public function testBehaviorMethod(): string
            {
                return implode(',', [
                    $this->owner->entries()::class,
                    $this->owner->cp::class,
                    $this->owner->app === Craft::$app ? 'app' : 'no-app',
                    isset($this->owner->app, $this->owner->cp) && !isset($this->owner->bogus) && !isset($this->owner->testBehaviorRequired) ? 'isset' : 'no-isset',
                ]);
            }

            public function testBehaviorRequired(string $suffix): string
            {
                return $suffix;
            }
        });
    });

    expect(app(TemplateManager::class)->renderString('{{ craft.testBehaviorMethod() }}'))
        ->toBe(EntryQuery::class . ',' . Cp::class . ',app,isset')
        ->and($inits)->toBe(1);
});

it('calls behavior methods on the legacy variable', function() {
    $variable = new LegacyCraftVariable();
    $variable->attachBehavior('test', new class() extends Behavior {
        public function testBehaviorMethod(string $suffix): string
        {
            return "method-$suffix";
        }
    });

    expect($variable->testBehaviorMethod('called'))->toBe('method-called')
        ->and($variable->environment())->toBe(app()->environment());
});
