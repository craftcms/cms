<?php

declare(strict_types=1);

use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\Resolvers\MutationResolver;
use GraphQL\Error\Error;

beforeEach(function () {
    app(Gql::class)->flushCaches();
});

function createConcreteMutationResolver(array $resolutionData = [], array $normalizers = []): MutationResolver
{
    return new class($resolutionData, $normalizers) extends MutationResolver
    {
        public function publicNormalizeValue(string $argument, mixed $value): mixed
        {
            return $this->normalizeValue($argument, $value);
        }

        public function publicRequireSchemaAction(string $scope, string $action): void
        {
            $this->requireSchemaAction($scope, $action);
        }
    };
}

it('stores and retrieves resolution data', function () {
    $resolver = createConcreteMutationResolver();

    $resolver->setResolutionData('section', 'news');
    $resolver->setResolutionData('entryType', 'article');

    expect($resolver->getResolutionData('section'))->toBe('news')
        ->and($resolver->getResolutionData('entryType'))->toBe('article');
});

it('returns null for missing resolution data keys', function () {
    $resolver = createConcreteMutationResolver();

    expect($resolver->getResolutionData('nonexistent'))->toBeNull();
});

it('accepts resolution data via constructor', function () {
    $resolver = createConcreteMutationResolver(['key' => 'value']);

    expect($resolver->getResolutionData('key'))->toBe('value');
});

it('normalizes values using registered normalizers', function () {
    $resolver = createConcreteMutationResolver();

    $resolver->setValueNormalizer('price', fn ($value) => (float) $value * 100);

    expect($resolver->publicNormalizeValue('price', '12.50'))->toBe(1250.0);
});

it('returns value unchanged when no normalizer is registered', function () {
    $resolver = createConcreteMutationResolver();

    expect($resolver->publicNormalizeValue('title', 'Hello World'))->toBe('Hello World');
});

it('can remove a normalizer by setting null', function () {
    $resolver = createConcreteMutationResolver();

    $resolver->setValueNormalizer('price', fn ($value) => (float) $value * 100);
    $resolver->setValueNormalizer('price');

    expect($resolver->publicNormalizeValue('price', '12.50'))->toBe('12.50');
});

it('accepts normalizers via constructor', function () {
    $resolver = createConcreteMutationResolver([], [
        'count' => fn ($v) => (int) $v,
    ]);

    expect($resolver->publicNormalizeValue('count', '42'))->toBe(42);
});

it('passes requireSchemaAction when schema allows the action', function () {
    app(Gql::class)->setActiveSchema(new GqlSchema([
        'scope' => ['sections.news:save'],
    ]));

    $resolver = createConcreteMutationResolver();

    // Should not throw
    $resolver->publicRequireSchemaAction('sections.news', 'save');

    expect(true)->toBeTrue();
});

it('throws Error when schema does not allow the action', function () {
    app(Gql::class)->setActiveSchema(new GqlSchema([
        'scope' => ['sections.news:read'],
    ]));

    $resolver = createConcreteMutationResolver();

    $resolver->publicRequireSchemaAction('sections.news', 'save');
})->throws(Error::class, 'Unable to perform the action.');

it('throws Error when schema has no matching scope at all', function () {
    app(Gql::class)->setActiveSchema(new GqlSchema([
        'scope' => [],
    ]));

    $resolver = createConcreteMutationResolver();

    $resolver->publicRequireSchemaAction('sections.news', 'read');
})->throws(Error::class, 'Unable to perform the action.');
