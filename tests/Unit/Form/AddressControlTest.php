<?php

declare(strict_types=1);

use CraftCms\Cms\Form\Controls\Address;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use Symfony\Component\DomCrawler\Crawler;

it('derives address fields from the country and current subdivisions', function () {
    $form = Form::make([
        Field::make()->control(Address::make('address')->countryCode('US')),
    ]);
    $payload = app(FormResolver::class)->resolve($form, new FormContext(
        namespace: 'settings',
        values: ['settings' => ['address' => [
            'administrativeArea' => 'invalid-state',
            'locality' => 'Portland',
        ]]],
    ));
    $fields = collect($payload->nodes[0]->control?->props['fields'])->keyBy('name');

    expect($fields['administrativeArea'])->toMatchArray([
        'type' => 'select',
        'visible' => true,
        'required' => true,
    ])->and($fields['administrativeArea']['options']['invalid-state'])->toBe('invalid-state')
        ->and($fields['locality'])->toMatchArray([
            'type' => 'text',
            'visible' => true,
            'required' => true,
        ]);
});

it('renders the canonical address map as nested inputs', function () {
    $form = Form::make([
        Field::make()->control(Address::make('address')->countryCode('BE')),
    ]);
    $payload = app(FormResolver::class)->resolve($form, new FormContext(
        namespace: 'settings',
        values: ['settings' => ['address' => [
            'addressLine1' => 'Museumstraat 1',
            'locality' => 'Antwerp',
            'postalCode' => '2000',
        ]]],
    ));
    $crawler = new Crawler(app(FormHtmlRenderer::class)->render($payload));

    expect($crawler->filter('craft-field[data-mode="editable"] > craft-field-group[slot="input"] input[name="settings[address][locality]"]'))->toHaveCount(1)
        ->and($crawler->filter('input[name="settings[address][addressLine1]"]')->attr('value'))->toBe('Museumstraat 1')
        ->and($crawler->filter('input[name="settings[address][locality]"]')->attr('value'))->toBe('Antwerp')
        ->and($crawler->filter('input[name="settings[address][postalCode]"]')->attr('value'))->toBe('2000');
});
