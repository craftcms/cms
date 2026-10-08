<?php

declare(strict_types=1);

use CraftCms\Cms\Form\Controls\BooleanMenu;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use Symfony\Component\DomCrawler\Crawler;

afterEach(function () {
    unset($_SERVER['BOOLEAN_MENU_ON'], $_SERVER['BOOLEAN_MENU_OFF'], $_SERVER['BOOLEAN_MENU_TEXT']);
});

it('offers yes and no as a combobox that requires a match', function () {
    $payload = app(FormResolver::class)->resolve(
        Form::make([Field::make('Enabled', BooleanMenu::make('enabled'))]),
        new FormContext(values: ['enabled' => '1']),
    );
    $control = $payload->nodes[0]->control;
    $combobox = new Crawler(app(FormHtmlRenderer::class)->render($payload))->filter('craft-combobox[name="enabled"]');

    expect($control?->component)->toBe('craft:combobox')
        ->and($control?->props['requireOptionMatch'])->toBeTrue()
        ->and($control?->props['options'])->toBe([
            ['label' => 'Yes', 'value' => '1', 'data' => ['indicator' => ['variant' => 'success']]],
            ['label' => 'No', 'value' => '0', 'data' => ['indicator' => ['variant' => 'empty']]],
        ])
        ->and($payload->values)->toBe(['enabled' => '1'])
        ->and($combobox)->toHaveCount(1);
});

it('lists boolean environment variables with what they resolve to', function () {
    $_SERVER['BOOLEAN_MENU_ON'] = 'yes';
    $_SERVER['BOOLEAN_MENU_OFF'] = 'off';
    $_SERVER['BOOLEAN_MENU_TEXT'] = 'not a boolean';

    $payload = app(FormResolver::class)->resolve(
        Form::make([Field::make('Status', BooleanMenu::make('enabled')
            ->yesLabel('Enabled')
            ->noLabel('Disabled')
            ->includeEnvVars())]),
        new FormContext(values: ['enabled' => '$BOOLEAN_MENU_ON']),
    );
    $options = $payload->nodes[0]->control?->props['options'];
    $envOptions = collect($options[2]['options'])->keyBy('value');

    expect(array_column(array_slice($options, 0, 2), 'label'))->toBe(['Enabled', 'Disabled'])
        ->and($options[2]['type'])->toBe('optgroup')
        ->and($envOptions->get('$BOOLEAN_MENU_ON')['data'])->toBe([
            'boolean' => '1',
            'hint' => 'Enabled',
            'indicator' => ['variant' => 'success'],
        ])
        ->and($envOptions->get('$BOOLEAN_MENU_OFF')['data'])->toBe([
            'boolean' => '0',
            'hint' => 'Disabled',
            'indicator' => ['variant' => 'empty'],
        ])
        ->and($envOptions->has('$BOOLEAN_MENU_TEXT'))->toBeFalse()
        ->and($payload->values)->toBe(['enabled' => '$BOOLEAN_MENU_ON']);
});

it('maps a stored value to its option', function (bool|int|string|null $stored, string $option) {
    expect(BooleanMenu::optionValue($stored))->toBe($option);
})->with([
    'true' => [true, '1'],
    'false' => [false, '0'],
    'null' => [null, '0'],
    'truthy string' => ['true', '1'],
    'falsy string' => ['false', '0'],
    'one' => [1, '1'],
    'zero string' => ['0', '0'],
    'environment variable' => ['$SITE_ENABLED', '$SITE_ENABLED'],
]);
