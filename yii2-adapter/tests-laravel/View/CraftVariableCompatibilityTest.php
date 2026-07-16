<?php

use craft\web\twig\variables\CraftVariable as LegacyCraftVariable;
use CraftCms\Cms\Twig\TwigRenderer;
use CraftCms\Cms\Twig\Variables\CraftVariable as LaravelCraftVariable;
use yii\base\Event;

afterEach(function() {
    Event::off(LegacyCraftVariable::class, LegacyCraftVariable::EVENT_INIT);

    $macros = new ReflectionProperty(LaravelCraftVariable::class, 'macros');
    $values = $macros->getValue();
    unset($values['testVariable']);
    $macros->setValue(null, $values);
});

it('registers custom Twig variables from the legacy init event', function() {
    Event::on(LegacyCraftVariable::class, LegacyCraftVariable::EVENT_INIT, function(Event $event) {
        $event->sender->set('testVariable', new class() {
            public string $value = 'registered';
        });
    });

    expect(app(TwigRenderer::class)->renderString('{{ craft.testVariable.value }}'))->toBe('registered');
});
