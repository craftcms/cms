<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Gql\ArgumentManager;
use CraftCms\Cms\Gql\Contracts\ArgumentHandlerInterface;
use CraftCms\Cms\Gql\GqlArguments;
use CraftCms\Cms\Gql\Handlers\RelatedAssets;
use CraftCms\Cms\Gql\Handlers\RelatedEntries;
use CraftCms\Cms\Gql\Handlers\RelationArgumentHandler;

it('supports class and factory handlers registered by argument name', function () {
    $registry = app(GqlArguments::class);
    $registry->register('initial', MultiplyArgumentHandler::class);

    expect(new ArgumentManager()->prepareArguments([
        'initial' => 5,
        'multiplier' => 2,
    ]))->toBe([
        'initial' => 5,
        'multiplier' => 2,
        'result' => 10,
    ]);

    $factoryManager = null;
    $factory = function (ArgumentManager $argumentManager) use (&$factoryManager) {
        $factoryManager = $argumentManager;

        return new ReplaceArgumentHandler;
    };
    $registry->register('initial', $factory);
    $argumentManager = new ArgumentManager;

    expect($argumentManager->prepareArguments([
        'initial' => 5,
        'multiplier' => 2,
    ]))->toBe([
        'initial' => 5,
        'multiplier' => 2,
        'result' => 99,
    ])->and($factoryManager)->toBe($argumentManager);
});

it('injects container dependencies into class handlers', function () {
    $dependency = new ArgumentHandlerDependency;
    app()->instance(ArgumentHandlerDependency::class, $dependency);
    app(GqlArguments::class)->register('containerBuilt', ContainerBuiltArgumentHandler::class);

    expect(new ArgumentManager()->prepareArguments(['containerBuilt' => true]))
        ->toHaveKey('dependency', $dependency);
});

it('reuses relation handler memoization within one manager but not across managers', function () {
    MemoizedRelationArgumentHandler::$lookups = 0;
    app(GqlArguments::class)->register('memoizedRelation', MemoizedRelationArgumentHandler::class);

    $firstManager = new ArgumentManager;
    $secondManager = new ArgumentManager;
    $firstManager->prepareArguments(['memoizedRelation' => [1]]);
    $firstManager->prepareArguments(['memoizedRelation' => [1]]);
    $secondManager->prepareArguments(['memoizedRelation' => [1]]);

    expect(MemoizedRelationArgumentHandler::$lookups)->toBe(2);
});

it('binds manager-local handlers when they are set', function () {
    $argumentManager = new ArgumentManager;
    $argumentManager->prepareArguments([]);

    $argumentManager->setHandler('object', new ManagerLocalArgumentHandler);
    $argumentManager->setHandler('class', ManagerLocalArgumentHandler::class);
    $factoryReceivedManager = false;
    $argumentManager->setHandler('factory', function (ArgumentManager $manager) use (&$factoryReceivedManager, $argumentManager) {
        $factoryReceivedManager = $manager === $argumentManager;

        return new ManagerLocalArgumentHandler;
    });

    expect($argumentManager->prepareArguments([
        'object' => true,
        'class' => true,
        'factory' => true,
    ]))->toHaveKey('boundHandlers', 3)
        ->and($factoryReceivedManager)->toBeTrue();
});

it('rejects invalid factory results when handlers are first resolved', function () {
    app(GqlArguments::class)->register('invalid', fn () => new stdClass);

    expect(fn () => new ArgumentManager()->prepareArguments(['invalid' => true]))
        ->toThrow(
            InvalidArgumentException::class,
            sprintf(
                'Argument handler [invalid] must resolve to an instance of [%s].',
                ArgumentHandlerInterface::class,
            ),
        );
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

class MultiplyArgumentHandler implements ArgumentHandlerInterface
{
    public function handleArgumentCollection(array $argumentList = []): array
    {
        $argumentList['result'] = $argumentList['initial'] * $argumentList['multiplier'];

        return $argumentList;
    }

    public function setArgumentManager(ArgumentManager $argumentManager): void {}
}

class ReplaceArgumentHandler implements ArgumentHandlerInterface
{
    public function handleArgumentCollection(array $argumentList = []): array
    {
        $argumentList['result'] = 99;

        return $argumentList;
    }

    public function setArgumentManager(ArgumentManager $argumentManager): void {}
}

class ArgumentHandlerDependency {}

class ContainerBuiltArgumentHandler implements ArgumentHandlerInterface
{
    public function __construct(
        public ArgumentHandlerDependency $dependency,
    ) {}

    public function handleArgumentCollection(array $argumentList = []): array
    {
        $argumentList['dependency'] = $this->dependency;

        return $argumentList;
    }

    public function setArgumentManager(ArgumentManager $argumentManager): void {}
}

class MemoizedRelationArgumentHandler extends RelationArgumentHandler
{
    public static int $lookups = 0;

    #[Override]
    protected string $argumentName = 'memoizedRelation';

    #[Override]
    protected function handleArgument($argumentValue): mixed
    {
        self::$lookups++;

        return [[$argumentValue[0]]];
    }
}

class ManagerLocalArgumentHandler implements ArgumentHandlerInterface
{
    public ?ArgumentManager $argumentManager = null;

    public function handleArgumentCollection(array $argumentList = []): array
    {
        if ($this->argumentManager !== null) {
            $argumentList['boundHandlers'] = ($argumentList['boundHandlers'] ?? 0) + 1;
        }

        return $argumentList;
    }

    public function setArgumentManager(ArgumentManager $argumentManager): void
    {
        $this->argumentManager = $argumentManager;
    }
}
