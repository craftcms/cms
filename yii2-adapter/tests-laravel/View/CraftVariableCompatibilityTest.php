<?php

use craft\web\twig\variables\CraftVariable as LegacyCraftVariable;
use CraftCms\Cms\Twig\Variables\CraftVariable as LaravelCraftVariable;
use CraftCms\Cms\View\TemplateManager;
use yii\base\Behavior;
use yii\base\Event;

afterEach(function() {
    Event::off(LegacyCraftVariable::class, LegacyCraftVariable::EVENT_INIT);

    $macros = new ReflectionProperty(LaravelCraftVariable::class, 'macros');
    $values = $macros->getValue();
    unset($values['testVariable'], $values['testBehaviorValue'], $values['testBehaviorQuery']);
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

            public function testBehaviorQuery(array $criteria = []): string
            {
                return 'query:' . implode(',', $criteria);
            }
        });
    });

    expect(app(TemplateManager::class)->renderString(
        '{{ craft.testBehaviorValue }}|{{ craft.testBehaviorQuery }}|{{ craft.testBehaviorQuery(["a", "b"]) }}',
    ))->toBe('property|query:|query:a,b');
});
