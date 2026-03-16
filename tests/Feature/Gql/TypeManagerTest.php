<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Events\DefineGqlTypeFields;
use CraftCms\Cms\Gql\Gql;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    app(Gql::class)->flushCaches();
    app(Gql::class)->setActiveSchema(new GqlSchema);
    Cms::config()->enableGraphqlCaching = false;
});

it('allows type field definitions to be modified', function () {
    Event::listen(DefineGqlTypeFields::class, function (DefineGqlTypeFields $event) {
        $event->fields['otherField'] = 'otherThing';
    });

    expect(app(Gql::class)->prepareFieldDefinitions(['field' => 'something'], 'someName'))
        ->toBe([
            'field' => 'something',
            'otherField' => 'otherThing',
        ]);
});

it('flushes cached prepared field definitions', function () {
    $gql = app(Gql::class);

    $gql->prepareFieldDefinitions([], 'someName');

    expect($gql->prepareFieldDefinitions(['ok'], 'someName'))->toBe([]);

    $gql->flushCaches();

    expect($gql->prepareFieldDefinitions(['ok'], 'someName'))->toBe(['ok']);
});
