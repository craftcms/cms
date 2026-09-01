<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Dashboard\Widgets\CraftSupport;
use CraftCms\Cms\Dashboard\Widgets\Feed;
use CraftCms\Cms\Dashboard\Widgets\MyDrafts;
use CraftCms\Cms\Dashboard\Widgets\NewUsers;
use CraftCms\Cms\Dashboard\Widgets\QuickPost;
use CraftCms\Cms\Dashboard\Widgets\RecentEntries;
use CraftCms\Cms\Dashboard\Widgets\Updates as UpdatesWidget;
use CraftCms\Cms\Dashboard\WidgetTypes;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementTypes;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Addresses;
use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Field\ButtonGroup;
use CraftCms\Cms\Field\Checkboxes;
use CraftCms\Cms\Field\Color;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Country;
use CraftCms\Cms\Field\Date;
use CraftCms\Cms\Field\Dropdown;
use CraftCms\Cms\Field\Email;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\FieldTypes;
use CraftCms\Cms\Field\Icon;
use CraftCms\Cms\Field\Json;
use CraftCms\Cms\Field\Lightswitch;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\LinkTypes;
use CraftCms\Cms\Field\LinkTypes\Asset as LinkAsset;
use CraftCms\Cms\Field\LinkTypes\BaseLinkType;
use CraftCms\Cms\Field\LinkTypes\Email as LinkEmail;
use CraftCms\Cms\Field\LinkTypes\Entry as LinkEntry;
use CraftCms\Cms\Field\LinkTypes\Phone;
use CraftCms\Cms\Field\LinkTypes\Sms;
use CraftCms\Cms\Field\LinkTypes\Url;
use CraftCms\Cms\Field\Markdown;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Money;
use CraftCms\Cms\Field\MultiSelect;
use CraftCms\Cms\Field\Number;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Field\RadioButtons;
use CraftCms\Cms\Field\Range;
use CraftCms\Cms\Field\Table;
use CraftCms\Cms\Field\Time;
use CraftCms\Cms\Field\Users;
use CraftCms\Cms\Filesystem\Filesystems\Local;
use CraftCms\Cms\Filesystem\FilesystemTypes;
use CraftCms\Cms\Gql\Directives\Directive;
use CraftCms\Cms\Gql\Directives\FormatDateTime;
use CraftCms\Cms\Gql\Directives\Markdown as GqlMarkdown;
use CraftCms\Cms\Gql\Directives\Money as GqlMoney;
use CraftCms\Cms\Gql\Directives\ParseRefs;
use CraftCms\Cms\Gql\Directives\StripTags;
use CraftCms\Cms\Gql\Directives\Transform;
use CraftCms\Cms\Gql\Directives\Trim;
use CraftCms\Cms\Gql\GqlDirectives;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Utility\Utilities\AssetIndexes;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
use CraftCms\Cms\Utility\Utilities\DbBackup;
use CraftCms\Cms\Utility\Utilities\DeprecationErrors;
use CraftCms\Cms\Utility\Utilities\FindAndReplace;
use CraftCms\Cms\Utility\Utilities\Migrations;
use CraftCms\Cms\Utility\Utilities\PhpInfo;
use CraftCms\Cms\Utility\Utilities\ProjectConfig;
use CraftCms\Cms\Utility\Utilities\QueueManager;
use CraftCms\Cms\Utility\Utilities\SystemMessages;
use CraftCms\Cms\Utility\Utilities\SystemReport;
use CraftCms\Cms\Utility\Utilities\Updates;
use CraftCms\Cms\Utility\Utility;
use CraftCms\Cms\Utility\UtilityTypes;

beforeEach(function () {
    Volumes::shouldReceive('getAllVolumes')->andReturn(collect([new stdClass]));
});

it('contains its built-in types in order', function (string $registry, array $expected) {
    expect(app($registry)->types()->all())->toBe($expected);
})->with([
    'fields' => [FieldTypes::class, [
        Addresses::class,
        Assets::class,
        ButtonGroup::class,
        Checkboxes::class,
        Color::class,
        ContentBlock::class,
        Country::class,
        Date::class,
        Dropdown::class,
        Email::class,
        Entries::class,
        Icon::class,
        Json::class,
        Lightswitch::class,
        Link::class,
        Markdown::class,
        Matrix::class,
        Money::class,
        MultiSelect::class,
        Number::class,
        PlainText::class,
        RadioButtons::class,
        Range::class,
        Table::class,
        Time::class,
        Users::class,
    ]],
    'elements' => [ElementTypes::class, [
        Address::class,
        Asset::class,
        Entry::class,
        User::class,
    ]],
    'widgets' => [WidgetTypes::class, [
        Feed::class,
        CraftSupport::class,
        NewUsers::class,
        QuickPost::class,
        RecentEntries::class,
        MyDrafts::class,
        UpdatesWidget::class,
    ]],
    'utilities' => [UtilityTypes::class, [
        Updates::class,
        SystemReport::class,
        ProjectConfig::class,
        PhpInfo::class,
        SystemMessages::class,
        AssetIndexes::class,
        QueueManager::class,
        ClearCaches::class,
        DeprecationErrors::class,
        DbBackup::class,
        FindAndReplace::class,
        Migrations::class,
    ]],
    'filesystems' => [FilesystemTypes::class, [
        Local::class,
    ]],
    'link types' => [LinkTypes::class, [
        LinkAsset::class,
        LinkEmail::class,
        LinkEntry::class,
        Phone::class,
        Sms::class,
        Url::class,
    ]],
    'gql directives' => [GqlDirectives::class, [
        FormatDateTime::class,
        GqlMarkdown::class,
        GqlMoney::class,
        StripTags::class,
        Trim::class,
        ParseRefs::class,
        Transform::class,
    ]],
]);

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
    'filesystems' => FilesystemTypes::class,
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
