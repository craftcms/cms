<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Attributes\ElementProperty;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Enums\ElementPropertyType;
use CraftCms\Cms\Element\Enums\ElementQueryParamType;
use CraftCms\Cms\Element\Queries\Attributes\ElementQueryParam;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Gql\ArgumentManager;
use CraftCms\Cms\Gql\Arguments\ElementArguments;
use CraftCms\Cms\Gql\Arguments\ElementMutationArguments;
use CraftCms\Cms\Gql\Arguments\Transform;
use CraftCms\Cms\Gql\Contracts\ArgumentHandlerInterface;
use CraftCms\Cms\Gql\GqlArguments;
use CraftCms\Cms\Gql\Handlers\RelatedAssets;
use CraftCms\Cms\Gql\Handlers\RelatedEntries;
use CraftCms\Cms\Gql\Handlers\RelatedUsers;
use CraftCms\Cms\Gql\Handlers\Site;
use CraftCms\Cms\Gql\Handlers\SiteId;
use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\StringType;

it('contains its built-in handlers in order', function () {
    expect(app(GqlArguments::class)->handlers()->all())->toBe([
        'relatedToEntries' => RelatedEntries::class,
        'relatedToAssets' => RelatedAssets::class,
        'relatedToUsers' => RelatedUsers::class,
        'site' => Site::class,
        'siteId' => SiteId::class,
    ]);
});

it('updates and removes handlers without changing their position or instantiating them', function () {
    RegistryArgumentHandler::$instances = 0;
    $factoryCalls = 0;
    $factory = function () use (&$factoryCalls) {
        $factoryCalls++;

        return new RegistryArgumentHandler;
    };
    $registry = app(GqlArguments::class);

    $registry->register('custom', RegistryArgumentHandler::class);
    $registry->register('afterCustom', $factory);
    $keys = $registry->handlers()->keys()->all();
    $registry->register('custom', AnotherRegistryArgumentHandler::class);
    $keysAfterUpdate = $registry->handlers()->keys()->all();
    $registry->remove('site', 'missing');

    $snapshot = $registry->handlers();
    $snapshot->put('snapshotOnly', RegistryArgumentHandler::class);

    expect($keysAfterUpdate)->toBe($keys)
        ->and($registry->handlers()->get('custom'))->toBe(AnotherRegistryArgumentHandler::class)
        ->and($registry->handlers())->not()->toHaveKey('site')
        ->and($registry->handlers())->not()->toHaveKey('snapshotOnly')
        ->and(RegistryArgumentHandler::$instances)->toBe(0)
        ->and($factoryCalls)->toBe(0);
});

it('rejects invalid handler registrations', function (string $name, string $handler) {
    expect(fn () => app(GqlArguments::class)->register($name, $handler))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'empty name' => ['', RegistryArgumentHandler::class],
    'invalid contract' => ['custom', stdClass::class],
]);

it('rejects invalid handler removals', function () {
    expect(fn () => app(GqlArguments::class)->remove(''))
        ->toThrow(InvalidArgumentException::class);
});

it('derives transform argument types from the operation catalogue', function () {
    $arguments = Transform::getArguments();

    expect($arguments['fill']['type'])->toBeInstanceOf(StringType::class)
        ->and($arguments['height']['type'])->toBeInstanceOf(IntType::class)
        ->and($arguments['upscale']['type'])->toBeInstanceOf(BooleanType::class)
        ->and($arguments)->not()->toHaveKey('immediately');
});

it('adds the query params of an element type that its arguments class names', function () {
    $arguments = GqlArgumentsWidgetArguments::getArguments();

    expect($arguments['color']['description'])->toBe('Narrows the query results based on the widgets’ colors.')
        ->and((string) $arguments['color']['type'])->toBe('[String]')
        ->and($arguments['site']['description'])->toBe('Determines which site(s) the elements should be queried in. Defaults to the current (requested) site.')
        ->and($arguments)->not()->toHaveKey('drafts')
        ->and(GqlArgumentsWidgetArgumentsWithoutElementType::getArguments())->not()->toHaveKey('color');
});

it('adds the properties of an element type that its mutation arguments class names', function () {
    $arguments = GqlArgumentsWidgetMutationArguments::getArguments();

    expect($arguments['color']['description'])->toBe('The widget’s color.')
        ->and((string) $arguments['color']['type'])->toBe('String')
        ->and($arguments['title']['description'])->toBe('The title of the element.')
        ->and($arguments)->not()->toHaveKey('slug')
        ->and(GqlArgumentsWidgetMutationArgumentsWithoutElementType::getArguments())->not()->toHaveKey('color');
});

class GqlArgumentsWidget extends Element
{
    #[ElementProperty(ElementPropertyType::String, 'The {element}’s color.', nullable: true)]
    public ?string $color = null;

    public static function displayName(): string
    {
        return 'Widget';
    }

    public static function pluralDisplayName(): string
    {
        return 'Widgets';
    }

    public static function find(): ElementQueryInterface
    {
        return new GqlArgumentsWidgetQuery(static::class);
    }
}

class GqlArgumentsWidgetQuery extends ElementQuery
{
    #[ElementQueryParam(ElementQueryParamType::StringOrStrings, 'Narrows the query results based on the {elements}’ colors.')]
    public function color(mixed $value): static
    {
        return $this;
    }
}

class GqlArgumentsWidgetArguments extends ElementArguments
{
    protected static function elementType(): string
    {
        return GqlArgumentsWidget::class;
    }
}

class GqlArgumentsWidgetArgumentsWithoutElementType extends ElementArguments {}

class GqlArgumentsWidgetMutationArguments extends ElementMutationArguments
{
    protected static function elementType(): string
    {
        return GqlArgumentsWidget::class;
    }
}

class GqlArgumentsWidgetMutationArgumentsWithoutElementType extends ElementMutationArguments {}

class RegistryArgumentHandler implements ArgumentHandlerInterface
{
    public static int $instances = 0;

    public function __construct()
    {
        self::$instances++;
    }

    public function handleArgumentCollection(array $argumentList = []): array
    {
        return $argumentList;
    }

    public function setArgumentManager(ArgumentManager $argumentManager): void {}
}

class AnotherRegistryArgumentHandler extends RegistryArgumentHandler {}
