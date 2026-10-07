<?php

declare(strict_types=1);

use CraftCms\Cms\Ui\Controls\Address;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use Symfony\Component\DomCrawler\Crawler;

it('derives address fields from the country and current subdivisions', function () {
    $form = Ui::make([
        Field::make()->control(Address::make('address')->countryCode('US')),
    ]);
    $payload = app(UiResolver::class)->resolve($form, new UiContext(
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
    $form = Ui::make([
        Field::make()->control(Address::make('address')->countryCode('BE')),
    ]);
    $payload = app(UiResolver::class)->resolve($form, new UiContext(
        namespace: 'settings',
        values: ['settings' => ['address' => [
            'addressLine1' => 'Museumstraat 1',
            'locality' => 'Antwerp',
            'postalCode' => '2000',
        ]]],
    ));
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));

    expect($crawler->filter('craft-field[data-mode="editable"] > craft-field-group[slot="input"] input[name="settings[address][locality]"]'))->toHaveCount(1)
        ->and($crawler->filter('input[name="settings[address][addressLine1]"]')->attr('value'))->toBe('Museumstraat 1')
        ->and($crawler->filter('input[name="settings[address][locality]"]')->attr('value'))->toBe('Antwerp')
        ->and($crawler->filter('input[name="settings[address][postalCode]"]')->attr('value'))->toBe('2000');
});
