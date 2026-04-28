<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Gql\ArgumentManager;
use CraftCms\Cms\Gql\Contracts\ArgumentHandlerInterface;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Events\RegisterGqlArgumentHandlers;
use CraftCms\Cms\Gql\Events\RegisterGqlQueries;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\Handlers\RelatedAssets;
use CraftCms\Cms\Gql\Handlers\RelatedEntries;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    app(Gql::class)->flushCaches();
    app(Gql::class)->setActiveSchema(new GqlSchema);
    Cms::config()->enableGraphqlCaching = false;
});

it('registers custom argument handlers for gql execution', function () {
    Event::listen(RegisterGqlQueries::class, function (RegisterGqlQueries $event) {
        $event->queries['integrationQuery'] = [
            'type' => Type::string(),
            'args' => [
                'initial' => Type::int(),
                'multiplier' => Type::int(),
                'result' => Type::int(),
                'wipeInitial' => Type::boolean(),
            ],
            'resolve' => function ($source, array $arguments, $context, ResolveInfo $resolveInfo) {
                if (! empty($context['argumentManager'])) {
                    $arguments = $context['argumentManager']->prepareArguments($arguments);
                }

                ksort($arguments);

                return json_encode($arguments);
            },
        ];
    });

    $handler = new class implements ArgumentHandlerInterface
    {
        public function handleArgumentCollection(array $argumentList = []): array
        {
            $argumentList['result'] = $argumentList['initial'] * $argumentList['multiplier'];

            if (! empty($argumentList['wipeInitial'])) {
                unset($argumentList['initial']);
            }

            return $argumentList;
        }

        public function setArgumentManager(ArgumentManager $argumentManager): void {}
    };

    Event::listen(RegisterGqlArgumentHandlers::class, function (RegisterGqlArgumentHandlers $event) use ($handler) {
        $event->handlers['initial'] = $handler;
    });

    $result = app(Gql::class)->executeQuery(new GqlSchema(['id' => 1]), '{integrationQuery (initial: 5 multiplier: 2)}');

    expect(json_decode((string) $result['data']['integrationQuery'], true))->toBe([
        'initial' => 5,
        'multiplier' => 2,
        'result' => 10,
    ]);
});

it('prepares relation arguments with the registered handlers', function () {
    $argumentManager = new ArgumentManager;

    $relatedAssets = new class extends RelatedAssets
    {
        protected function getIds(string $elementType, array $criteriaList = []): array
        {
            expect($elementType)->toBe(Asset::class);

            return [[1, 2]];
        }
    };
    $relatedEntries = new class extends RelatedEntries
    {
        protected function getIds(string $elementType, array $criteriaList = []): array
        {
            expect($elementType)->toBe(Entry::class);

            return [[3], [4]];
        }
    };

    $relatedAssets->setArgumentManager($argumentManager);
    $relatedEntries->setArgumentManager($argumentManager);

    $argumentManager->setHandler('relatedToAssets', $relatedAssets);
    $argumentManager->setHandler('relatedToEntries', $relatedEntries);

    expect($argumentManager->prepareArguments([
        'relatedToAssets' => [['id' => 1]],
        'relatedToEntries' => [['id' => 2]],
    ]))->toBe([
        'relatedTo' => [
            'and',
            ['element' => [3]],
            ['element' => [4]],
            ['element' => [1, 2]],
        ],
    ]);
});
