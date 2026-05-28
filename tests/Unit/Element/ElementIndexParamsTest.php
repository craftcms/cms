<?php

declare(strict_types=1);

use CraftCms\Cms\Element\ElementIndexParams;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;

describe('ElementIndexParams::fromContext', function () {
    it('creates params with defaults', function () {
        $params = ElementIndexParams::fromContext(
            elementType: Entry::class,
            sourceKey: null,
        );

        expect($params->elementType)->toBe(Entry::class)
            ->and($params->sourceKey)->toBeNull()
            ->and($params->source)->toBeNull()
            ->and($params->context)->toBe(ElementSources::CONTEXT_INDEX)
            ->and($params->page)->toBe(1)
            ->and($params->perPage)->toBe(100)
            ->and($params->sort)->toBe([])
            ->and($params->isAdministrative)->toBeTrue()
            ->and($params->includeActions)->toBeTrue()
            ->and($params->includeContainer)->toBeTrue();
    });

    it('accepts custom pagination and sort', function () {
        $sort = [['field' => 'title', 'direction' => 'asc']];

        $params = ElementIndexParams::fromContext(
            elementType: Entry::class,
            sourceKey: null,
            page: 3,
            perPage: 25,
            sort: $sort,
        );

        expect($params->page)->toBe(3)
            ->and($params->perPage)->toBe(25)
            ->and($params->sort)->toBe($sort);
    });

    it('sets isAdministrative false for non-index contexts', function () {
        $params = ElementIndexParams::fromContext(
            elementType: Entry::class,
            sourceKey: null,
            context: ElementSources::CONTEXT_MODAL,
        );

        expect($params->isAdministrative)->toBeFalse();
    });

    it('sets isAdministrative true for embedded index context', function () {
        $params = ElementIndexParams::fromContext(
            elementType: Entry::class,
            sourceKey: null,
            context: ElementSources::CONTEXT_EMBEDDED_INDEX,
        );

        expect($params->isAdministrative)->toBeTrue();
    });

    it('passes criteria through', function () {
        $params = ElementIndexParams::fromContext(
            elementType: Entry::class,
            sourceKey: null,
            criteria: ['title' => 'Test'],
            baseCriteria: ['status' => 'live'],
        );

        expect($params->criteria)->toBe(['title' => 'Test'])
            ->and($params->baseCriteria)->toBe(['status' => 'live']);
    });
});

describe('ElementIndexParams constructor', function () {
    it('uses default viewState with table mode', function () {
        $params = new ElementIndexParams(
            elementType: Entry::class,
        );

        expect($params->viewState)->toBe(['mode' => 'table']);
    });

    it('accepts all parameters', function () {
        $params = new ElementIndexParams(
            elementType: Entry::class,
            sourceKey: 'section:123',
            source: ['type' => ElementSources::TYPE_NATIVE, 'key' => 'section:123'],
            viewState: ['mode' => 'cards'],
            context: ElementSources::CONTEXT_MODAL,
            disabledElementIds: [1, 2, 3],
            returnUrl: '/entries',
            isAdministrative: false,
            selectable: true,
            sortable: true,
            includeContainer: false,
            includeActions: false,
            page: 2,
            perPage: 50,
            sort: [['field' => 'title', 'direction' => 'desc']],
        );

        expect($params->sourceKey)->toBe('section:123')
            ->and($params->viewState)->toBe(['mode' => 'cards'])
            ->and($params->context)->toBe(ElementSources::CONTEXT_MODAL)
            ->and($params->disabledElementIds)->toBe([1, 2, 3])
            ->and($params->returnUrl)->toBe('/entries')
            ->and($params->isAdministrative)->toBeFalse()
            ->and($params->selectable)->toBeTrue()
            ->and($params->sortable)->toBeTrue()
            ->and($params->includeContainer)->toBeFalse()
            ->and($params->includeActions)->toBeFalse()
            ->and($params->page)->toBe(2)
            ->and($params->perPage)->toBe(50)
            ->and($params->sort)->toBe([['field' => 'title', 'direction' => 'desc']]);
    });
});
