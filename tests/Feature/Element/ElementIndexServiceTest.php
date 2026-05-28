<?php

declare(strict_types=1);

use CraftCms\Cms\Element\ElementIndexParams;
use CraftCms\Cms\Element\ElementIndexService;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
});

describe('buildQueryState', function () {
    it('returns a query with id(false) when source is null', function () {
        $service = app(ElementIndexService::class);
        $params = ElementIndexParams::fromContext(
            elementType: Entry::class,
            sourceKey: null,
        );

        $result = $service->buildQueryState($params);

        expect($result['query'])->not->toBeNull()
            ->and($result['unfilteredQuery'])->toBeNull();
    });

    it('returns a valid query when source is resolved', function () {
        $service = app(ElementIndexService::class);
        $params = new ElementIndexParams(
            elementType: Entry::class,
            sourceKey: '*',
            source: [
                'type' => ElementSources::TYPE_NATIVE,
                'key' => '*',
                'label' => 'All entries',
            ],
        );

        $result = $service->buildQueryState($params);

        expect($result['query'])->not->toBeNull()
            ->and($result['unfilteredQuery'])->toBeNull();
    });
});

describe('getElementsJson', function () {
    it('returns paginated elements with correct structure', function () {
        EntryModel::factory()->count(3)->create();

        $service = app(ElementIndexService::class);
        $params = new ElementIndexParams(
            elementType: Entry::class,
            sourceKey: '*',
            source: [
                'type' => ElementSources::TYPE_NATIVE,
                'key' => '*',
                'label' => 'All entries',
            ],
            perPage: 2,
            page: 1,
        );

        $result = $service->getElementsJson($params);

        expect($result)->toHaveKeys(['elements', 'pagination'])
            ->and($result['elements'])->toHaveCount(2)
            ->and($result['pagination']['total'])->toBe(3)
            ->and($result['pagination']['per_page'])->toBe(2)
            ->and($result['pagination']['current_page'])->toBe(1)
            ->and($result['pagination']['last_page'])->toBe(2)
            ->and($result['pagination']['from'])->toBe(1)
            ->and($result['pagination']['to'])->toBe(2);
    });

    it('returns second page of results', function () {
        EntryModel::factory()->count(3)->create();

        $service = app(ElementIndexService::class);
        $params = new ElementIndexParams(
            elementType: Entry::class,
            sourceKey: '*',
            source: [
                'type' => ElementSources::TYPE_NATIVE,
                'key' => '*',
                'label' => 'All entries',
            ],
            perPage: 2,
            page: 2,
        );

        $result = $service->getElementsJson($params);

        expect($result['elements'])->toHaveCount(1)
            ->and($result['pagination']['current_page'])->toBe(2)
            ->and($result['pagination']['from'])->toBe(3)
            ->and($result['pagination']['to'])->toBe(3);
    });

    it('serializes element data correctly', function () {
        EntryModel::factory()->createElement(['title' => 'My Test Entry']);

        $service = app(ElementIndexService::class);
        $params = new ElementIndexParams(
            elementType: Entry::class,
            sourceKey: '*',
            source: [
                'type' => ElementSources::TYPE_NATIVE,
                'key' => '*',
                'label' => 'All entries',
            ],
        );

        $result = $service->getElementsJson($params);
        $element = $result['elements'][0];

        expect($element)->toHaveKeys(['id', 'title', 'slug', 'status', 'enabled', 'dateCreated', 'dateUpdated'])
            ->and($element['title'])->toBe('My Test Entry')
            ->and($element['enabled'])->toBeTrue();
    });

    it('applies sorting', function () {
        EntryModel::factory()->createElement(['title' => 'Charlie']);
        EntryModel::factory()->createElement(['title' => 'Alpha']);
        EntryModel::factory()->createElement(['title' => 'Bravo']);

        $service = app(ElementIndexService::class);
        $params = new ElementIndexParams(
            elementType: Entry::class,
            sourceKey: '*',
            source: [
                'type' => ElementSources::TYPE_NATIVE,
                'key' => '*',
                'label' => 'All entries',
            ],
            sort: [['field' => 'title', 'direction' => 'asc']],
        );

        $result = $service->getElementsJson($params);
        $titles = array_column($result['elements'], 'title');

        expect($titles)->toBe(['Alpha', 'Bravo', 'Charlie']);
    });

    it('applies descending sort', function () {
        EntryModel::factory()->createElement(['title' => 'Alpha']);
        EntryModel::factory()->createElement(['title' => 'Bravo']);

        $service = app(ElementIndexService::class);
        $params = new ElementIndexParams(
            elementType: Entry::class,
            sourceKey: '*',
            source: [
                'type' => ElementSources::TYPE_NATIVE,
                'key' => '*',
                'label' => 'All entries',
            ],
            sort: [['field' => 'title', 'direction' => 'desc']],
        );

        $result = $service->getElementsJson($params);
        $titles = array_column($result['elements'], 'title');

        expect($titles)->toBe(['Bravo', 'Alpha']);
    });

    it('returns empty elements when source is null', function () {
        EntryModel::factory()->count(2)->create();

        $service = app(ElementIndexService::class);
        $params = ElementIndexParams::fromContext(
            elementType: Entry::class,
            sourceKey: null,
        );

        $result = $service->getElementsJson($params);

        expect($result['elements'])->toBeEmpty()
            ->and($result['pagination']['total'])->toBe(0);
    });

    it('clamps page to last page when exceeding total', function () {
        EntryModel::factory()->count(3)->create();

        $service = app(ElementIndexService::class);
        $params = new ElementIndexParams(
            elementType: Entry::class,
            sourceKey: '*',
            source: [
                'type' => ElementSources::TYPE_NATIVE,
                'key' => '*',
                'label' => 'All entries',
            ],
            perPage: 2,
            page: 99,
        );

        $result = $service->getElementsJson($params);

        expect($result['pagination']['current_page'])->toBe(2)
            ->and($result['elements'])->toHaveCount(1);
    });
});

describe('getElementsHtml', function () {
    it('returns HTML response with expected keys', function () {
        EntryModel::factory()->create();

        $service = app(ElementIndexService::class);
        $params = new ElementIndexParams(
            elementType: Entry::class,
            sourceKey: '*',
            source: [
                'type' => ElementSources::TYPE_NATIVE,
                'key' => '*',
                'label' => 'All entries',
            ],
        );

        $result = $service->getElementsHtml($params);

        expect($result)->toHaveKeys(['html', 'headHtml', 'bodyHtml', 'actions', 'actionsHeadHtml', 'actionsBodyHtml', 'exporters']);
    });

    it('returns nothing-yet message when source is null', function () {
        $service = app(ElementIndexService::class);
        $params = ElementIndexParams::fromContext(
            elementType: Entry::class,
            sourceKey: null,
        );

        $result = $service->getElementsHtml($params);

        expect($result['html'])->toContain('Nothing yet');
    });
});
