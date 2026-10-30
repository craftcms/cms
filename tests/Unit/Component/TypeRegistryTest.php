<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\WidgetTypes;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementTypes;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\FieldTypes;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\LinkTypes;
use CraftCms\Cms\Field\LinkTypes\BaseLinkType;
use CraftCms\Cms\Field\LinkTypes\Url;
use CraftCms\Cms\Gql\Directives\Directive;
use CraftCms\Cms\Gql\GqlDirectives;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Utility\Utility;
use CraftCms\Cms\Utility\UtilityTypes;

beforeEach(function () {
    Volumes::shouldReceive('getAllVolumes')->andReturn(collect([new stdClass]));
});

it('registers types once in first-registration order', function () {
    $registry = app(UtilityTypes::class);

    $registry->register(RegistryUtilityType::class, AnotherRegistryUtilityType::class, RegistryUtilityType::class);

    expect($registry->types()->intersect([RegistryUtilityType::class, AnotherRegistryUtilityType::class])->values()->all())
        ->toBe([RegistryUtilityType::class, AnotherRegistryUtilityType::class]);
});

it('removes registered types idempotently', function () {
    $registry = app(UtilityTypes::class);

    $registry->register(RegistryUtilityType::class, AnotherRegistryUtilityType::class);
    $registry->remove(RegistryUtilityType::class);

    expect($registry->types())->not()->toContain(RegistryUtilityType::class)
        ->and($registry->types())->toContain(AnotherRegistryUtilityType::class);

    $registry->remove(AnotherRegistryUtilityType::class, RegistryUtilityType::class);
    $registry->remove(AnotherRegistryUtilityType::class);

    expect($registry->types())
        ->not()->toContain(RegistryUtilityType::class, AnotherRegistryUtilityType::class);
});

it('rejects removing required types', function (string $registry, string $type) {
    $registry = app($registry);

    expect(fn () => $registry->remove($type))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'URL link type' => [LinkTypes::class, Url::class],
]);

it('rejects types that do not satisfy the registry contract', function (string $registry) {
    expect(fn () => app($registry)->register(stdClass::class))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'fields' => FieldTypes::class,
    'elements' => ElementTypes::class,
    'widgets' => WidgetTypes::class,
    'utilities' => UtilityTypes::class,
    'link types' => LinkTypes::class,
    'gql directives' => GqlDirectives::class,
]);

it('does not partially register a rejected batch', function () {
    $registry = app(FieldTypes::class);

    expect(fn () => $registry->register(RegistryFieldType::class, stdClass::class))
        ->toThrow(InvalidArgumentException::class)
        ->and($registry->types())->not()->toContain(RegistryFieldType::class);
});

it('returns snapshots that cannot mutate stored types', function () {
    $registry = app(UtilityTypes::class);
    $snapshot = $registry->types();

    $snapshot->push(RegistryUtilityType::class);

    expect($registry->types())->not()->toContain(RegistryUtilityType::class);
});

it('runs deferred callbacks on the first read', function () {
    $registry = app(UtilityTypes::class);
    $runs = 0;

    $registry->defer(function (UtilityTypes $registry) use (&$runs) {
        $runs++;
        $registry->register(RegistryUtilityType::class);
    });

    expect($runs)->toBe(0);
    expect($registry->types())->toContain(RegistryUtilityType::class)
        ->and($runs)->toBe(1);

    $registry->types();

    expect($runs)->toBe(1);
});

it('lets a deferred callback read the registry without re-entering itself', function () {
    $registry = app(UtilityTypes::class);
    $seen = null;

    $registry->defer(function (UtilityTypes $registry) use (&$seen) {
        $seen = $registry->types();
        $registry->register(RegistryUtilityType::class);
    });

    $types = $registry->types();

    expect($seen)->not()->toContain(RegistryUtilityType::class)
        ->and($types)->toContain(RegistryUtilityType::class);
});

it('does not instantiate registered types', function () {
    RegistryUtilityType::$instances = 0;

    app(UtilityTypes::class)->register(RegistryUtilityType::class);

    expect(RegistryUtilityType::$instances)->toBe(0);
});

it('rejects domain identity collisions without partially registering the batch', function (string $registryClass, string $first, string $second) {
    $registry = app($registryClass);

    expect(fn () => $registry->register($first, $second))
        ->toThrow(InvalidArgumentException::class)
        ->and($registry->types())->not()->toContain($first, $second);
})->with([
    'link type IDs' => [LinkTypes::class, RegistryLinkType::class, CollidingRegistryLinkType::class],
    'element reference handles' => [ElementTypes::class, RegistryElementType::class, CollidingRegistryElementType::class],
    'utility IDs' => [UtilityTypes::class, RegistryUtilityType::class, CollidingRegistryUtilityType::class],
    'GQL directive names' => [GqlDirectives::class, RegistryGqlDirective::class, CollidingRegistryGqlDirective::class],
]);

it('keeps the protected URL link type effective when its identity is claimed', function () {
    $registry = app(LinkTypes::class);

    expect(fn () => $registry->register(UrlRegistryLinkType::class))
        ->toThrow(InvalidArgumentException::class)
        ->and($registry->types())->not()->toContain(UrlRegistryLinkType::class)
        ->and(Link::types()['url'])->toBe(Url::class);
});

it('rejects Webonyx built-in directive names', function () {
    $registry = app(GqlDirectives::class);
    ReservedRegistryGqlDirective::$testName = 'skip';

    expect(fn () => $registry->register(ReservedRegistryGqlDirective::class))
        ->toThrow(InvalidArgumentException::class)
        ->and($registry->types())->not()->toContain(ReservedRegistryGqlDirective::class);
});

it('allows element types without reference handles to coexist by class', function () {
    $registry = app(ElementTypes::class);

    $registry->register(NullRegistryElementType::class, AnotherNullRegistryElementType::class);

    expect($registry->types())
        ->toContain(NullRegistryElementType::class, AnotherNullRegistryElementType::class);
});

abstract class RegistryFieldType extends Field {}

abstract class RegistryElementType extends Element
{
    #[Override]
    public static function refHandle(): ?string
    {
        return 'registryElement';
    }
}

abstract class CollidingRegistryElementType extends RegistryElementType
{
    #[Override]
    public static function refHandle(): ?string
    {
        return 'REGISTRYELEMENT';
    }
}

abstract class NullRegistryElementType extends Element {}

abstract class AnotherNullRegistryElementType extends Element {}

class RegistryUtilityType extends Utility
{
    public static int $instances = 0;

    public function __construct()
    {
        self::$instances++;
    }

    public static function displayName(): string
    {
        return 'Registry utility';
    }

    public static function id(): string
    {
        return 'registry-utility';
    }

    public static function contentHtml(): string
    {
        return '';
    }
}

class AnotherRegistryUtilityType extends RegistryUtilityType
{
    #[Override]
    public static function id(): string
    {
        return 'another-registry-utility';
    }
}

class CollidingRegistryUtilityType extends RegistryUtilityType {}

abstract class RegistryLinkType extends BaseLinkType
{
    #[Override]
    public static function id(): string
    {
        return 'registryLink';
    }
}

abstract class CollidingRegistryLinkType extends RegistryLinkType {}

abstract class UrlRegistryLinkType extends BaseLinkType
{
    #[Override]
    public static function id(): string
    {
        return 'url';
    }
}

abstract class RegistryGqlDirective extends Directive
{
    #[Override]
    public static function name(): string
    {
        return 'registry';
    }
}

abstract class CollidingRegistryGqlDirective extends RegistryGqlDirective {}

abstract class ReservedRegistryGqlDirective extends Directive
{
    public static string $testName;

    #[Override]
    public static function name(): string
    {
        return self::$testName;
    }
}
