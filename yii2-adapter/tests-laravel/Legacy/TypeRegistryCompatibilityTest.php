<?php

declare(strict_types=1);

use craft\base\Event as YiiEvent;
use craft\base\imagetransforms\ImageTransformerInterface;
use craft\events\RegisterAssetFileKindsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\fields\Link as LegacyLink;
use craft\helpers\Assets as LegacyAssets;
use craft\imagetransforms\ImageTransformer as LegacyImageTransformer;
use craft\services\Auth as LegacyAuth;
use craft\services\Dashboard as LegacyDashboard;
use craft\services\Elements as LegacyElements;
use craft\services\Fields as LegacyFields;
use craft\services\Fs as LegacyFilesystems;
use craft\services\ImageTransforms as LegacyImageTransforms;
use craft\services\Utilities as LegacyUtilities;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\AssetFileKinds;
use CraftCms\Cms\Auth\AuthMethodCatalog;
use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Auth\Methods\TOTP;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Dashboard\Widgets\CraftSupport;
use CraftCms\Cms\Dashboard\Widgets\Feed;
use CraftCms\Cms\Dashboard\Widgets\Widget;
use CraftCms\Cms\Dashboard\WidgetTypes;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\ElementTypes;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Color;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\FieldTypes;
use CraftCms\Cms\Field\LinkTypes;
use CraftCms\Cms\Field\LinkTypes\Asset as LinkAsset;
use CraftCms\Cms\Field\LinkTypes\Url;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\NestedEntryFieldTypes;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Filesystem\Filesystems;
use CraftCms\Cms\Filesystem\Filesystems\Local;
use CraftCms\Cms\Filesystem\FilesystemTypes;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Utility\Utilities\PhpInfo;
use CraftCms\Cms\Utility\Utilities\SystemReport;
use CraftCms\Cms\Utility\Utility;
use CraftCms\Cms\Utility\UtilityTypes;
use CraftCms\Yii2Adapter\Asset\ImageTransformers;
use Illuminate\Support\Collection;

beforeEach(function() {
    Volumes::shouldReceive('getAllVolumes')->andReturn(Collection::make())->byDefault();
});

afterEach(function() {
    foreach ([
        [LegacyAssets::class, LegacyAssets::EVENT_REGISTER_FILE_KINDS],
        [LegacyAuth::class, LegacyAuth::EVENT_REGISTER_METHODS],
        [LegacyElements::class, LegacyElements::EVENT_REGISTER_ELEMENT_TYPES],
        [LegacyFields::class, LegacyFields::EVENT_REGISTER_FIELD_TYPES],
        [LegacyFields::class, LegacyFields::EVENT_REGISTER_NESTED_ENTRY_FIELD_TYPES],
        [LegacyDashboard::class, LegacyDashboard::EVENT_REGISTER_WIDGET_TYPES],
        [LegacyUtilities::class, LegacyUtilities::EVENT_REGISTER_UTILITIES],
        [LegacyFilesystems::class, LegacyFilesystems::EVENT_REGISTER_FILESYSTEM_TYPES],
        [LegacyImageTransforms::class, LegacyImageTransforms::EVENT_REGISTER_IMAGE_TRANSFORMERS],
        [LegacyLink::class, LegacyLink::EVENT_REGISTER_LINK_TYPES],
    ] as [$class, $event]) {
        YiiEvent::off($class, $event);
    }
});

it('applies legacy asset file kind listeners to the current definitions', function() {
    $fileKinds = app(AssetFileKinds::class);
    $fileKinds->register('modern', [
        'label' => 'Modern',
        'extensions' => ['modern'],
    ]);
    $calls = 0;

    YiiEvent::on(LegacyAssets::class, LegacyAssets::EVENT_REGISTER_FILE_KINDS, function(RegisterAssetFileKindsEvent $event) use (&$calls) {
        $calls++;
        expect($event->fileKinds)->toHaveKey('modern');
        $event->fileKinds = [
            'legacy' => [
                'label' => 'Legacy',
                'extensions' => ['legacy'],
            ],
        ];
    });

    expect($fileKinds->fileKinds())->toHaveKey('legacy')
        ->not()->toHaveKey('modern')
        ->and($fileKinds->fileKinds())->toHaveKey('legacy')
        ->and($calls)->toBe(2);
});

it('exposes Craft 5 element aliases through the legacy service and event', function() {
    $eventTypes = [];
    YiiEvent::on(LegacyElements::class, LegacyElements::EVENT_REGISTER_ELEMENT_TYPES, function(RegisterComponentTypesEvent $event) use (&$eventTypes) {
        $eventTypes = $event->types;
    });

    LegacyElements::finalizeRegistrationEvents();

    expect($eventTypes)
        ->toContain(craft\elements\Address::class, craft\elements\Entry::class)
        ->not()->toContain(Address::class, Entry::class)
        ->and(Craft::$app->getElements()->getAllElementTypes())
        ->toContain(craft\elements\Address::class, craft\elements\Entry::class)
        ->not()->toContain(Address::class, Entry::class)
        ->and(app(ElementTypes::class)->types())
        ->toContain(Address::class, Entry::class);
});

it('applies legacy utility listeners to the currently available types', function() {
    $seenTypes = [];
    YiiEvent::on(LegacyUtilities::class, LegacyUtilities::EVENT_REGISTER_UTILITIES, function(RegisterComponentTypesEvent $event) use (&$seenTypes) {
        $seenTypes[] = $event->types;
        $event->types[] = AdapterDisabledUtility::class;
        $key = array_search(SystemReport::class, $event->types, true);

        if ($key !== false) {
            unset($event->types[$key]);
        }
    });

    $config = Cms::config();
    $disabledUtilities = $config->disabledUtilities;

    try {
        $first = app(UtilityTypes::class)->types();
        $config->disabledUtilities = [PhpInfo::id(), AdapterDisabledUtility::id()];
        $second = app(UtilityTypes::class)->types();

        expect($first)->toContain(PhpInfo::class, AdapterDisabledUtility::class)
            ->not()->toContain(SystemReport::class)
            ->and($second)->not()->toContain(PhpInfo::class, SystemReport::class, AdapterDisabledUtility::class)
            ->and($seenTypes[0])->toContain(PhpInfo::class, SystemReport::class)
            ->and($seenTypes[1])->not()->toContain(PhpInfo::class);
    } finally {
        $config->disabledUtilities = $disabledUtilities;
    }
});

class AdapterDisabledUtility extends Utility
{
    public static function displayName(): string
    {
        return 'Adapter utility';
    }

    public static function id(): string
    {
        return 'adapter-utility';
    }

    public static function contentHtml(): string
    {
        return '';
    }
}

it('applies the legacy link type event through the registry and live link catalog while preserving the URL type', function() {
    app(LinkTypes::class)->register(AdapterRegistryLink::class);

    $eventCalls = 0;
    $modernTypeWasVisible = false;

    YiiEvent::on(LegacyLink::class, LegacyLink::EVENT_REGISTER_LINK_TYPES, function(RegisterComponentTypesEvent $event) use (&$eventCalls, &$modernTypeWasVisible) {
        $eventCalls++;
        $modernTypeWasVisible = in_array(AdapterRegistryLink::class, $event->types, true);
        $event->types = [AdapterRegistryLink::class];
    });

    LegacyLink::finalizeRegistrationEvents();

    $registryTypes = app(LinkTypes::class)->types();
    $types = LegacyLink::types();

    expect($registryTypes)->toContain(AdapterRegistryLink::class, Url::class)
        ->not()->toContain(LinkAsset::class)
        ->and($types)->toHaveKey('adapterRegistry', AdapterRegistryLink::class)
        ->toHaveKey('url', Url::class)
        ->not()->toHaveKeys(['asset', 'email', 'entry', 'phone', 'sms'])
        ->and(array_key_last($types))->toBe('url')
        ->and($eventCalls)->toBe(1)
        ->and($modernTypeWasVisible)->toBeTrue();

    YiiEvent::off(LegacyLink::class, LegacyLink::EVENT_REGISTER_LINK_TYPES);

    expect(LegacyLink::types())->toHaveKey('adapterRegistry', AdapterRegistryLink::class)
        ->toHaveKey('url', Url::class)
        ->not()->toHaveKeys(['asset', 'email', 'entry', 'phone', 'sms']);
});

it('rejects legacy link types that claim the protected URL identity', function() {
    $registry = app(LinkTypes::class);
    $types = $registry->types()->all();

    YiiEvent::on(LegacyLink::class, LegacyLink::EVENT_REGISTER_LINK_TYPES, function(RegisterComponentTypesEvent $event) {
        $event->types[] = AdapterUrlRegistryLink::class;
    });

    expect(fn() => LegacyLink::finalizeRegistrationEvents())
        ->toThrow(InvalidArgumentException::class)
        ->and($registry->types()->all())->toBe($types)
        ->and(LegacyLink::types()['url'])->toBe(Url::class);
});

it('applies legacy type registration events to a fresh modern registry snapshot', function(
    string $registryClass,
    string $modernType,
    string $legacyServiceClass,
    string $legacyEvent,
    string $serviceClass,
    string $getter,
    string $retainedType,
    string $removedType,
) {
    $registry = app($registryClass);
    $registry->register($modernType, $retainedType);

    $calls = 0;
    $modernTypeWasVisible = [];

    YiiEvent::on($legacyServiceClass, $legacyEvent, function(RegisterComponentTypesEvent $event) use (
        &$calls,
        &$modernTypeWasVisible,
        $modernType,
        $retainedType,
    ) {
        $calls++;
        $modernTypeWasVisible[] = in_array($modernType, $event->types, true);
        $event->types = [$modernType, $retainedType];
    });

    $legacyServiceClass::finalizeRegistrationEvents();

    $service = app($serviceClass);
    $firstRead = $registry->types();
    $secondRead = $service->{$getter}();
    $firstTypes = $firstRead instanceof Collection ? $firstRead->all() : $firstRead;
    $secondTypes = $secondRead instanceof Collection ? $secondRead->all() : $secondRead;

    expect($firstTypes)
        ->toContain($modernType, $retainedType)
        ->not()->toContain($removedType)
        ->and($secondTypes)
        ->toContain($modernType, $retainedType)
        ->not()->toContain($removedType)
        ->and($calls)->toBe(1)
        ->and($modernTypeWasVisible)->toBe([true]);

    YiiEvent::off($legacyServiceClass, $legacyEvent);

    $thirdRead = $registry->types();
    $thirdTypes = $thirdRead instanceof Collection ? $thirdRead->all() : $thirdRead;

    expect($thirdTypes)->toContain($modernType, $retainedType)
        ->not()->toContain($removedType);
})->with([
    'elements' => [
        ElementTypes::class,
        AdapterRegistryElement::class,
        LegacyElements::class,
        LegacyElements::EVENT_REGISTER_ELEMENT_TYPES,
        Elements::class,
        'getAllElementTypes',
        Entry::class,
        Address::class,
    ],
    'fields' => [
        FieldTypes::class,
        AdapterRegistryField::class,
        LegacyFields::class,
        LegacyFields::EVENT_REGISTER_FIELD_TYPES,
        Fields::class,
        'getAllFieldTypes',
        PlainText::class,
        Color::class,
    ],
    'authentication methods' => [
        AuthMethodCatalog::class,
        AdapterRegistryAuthMethod::class,
        LegacyAuth::class,
        LegacyAuth::EVENT_REGISTER_METHODS,
        AuthMethods::class,
        'types',
        AdapterRetainedAuthMethod::class,
        TOTP::class,
    ],
    'nested entry fields' => [
        NestedEntryFieldTypes::class,
        AdapterRegistryNestedEntryField::class,
        LegacyFields::class,
        LegacyFields::EVENT_REGISTER_NESTED_ENTRY_FIELD_TYPES,
        Fields::class,
        'getNestedEntryFieldTypes',
        AdapterRetainedNestedEntryField::class,
        Matrix::class,
    ],
    'widgets' => [
        WidgetTypes::class,
        AdapterRegistryWidget::class,
        LegacyDashboard::class,
        LegacyDashboard::EVENT_REGISTER_WIDGET_TYPES,
        LegacyDashboard::class,
        'getAllWidgetTypes',
        Feed::class,
        CraftSupport::class,
    ],
    'filesystems' => [
        FilesystemTypes::class,
        AdapterRegistryFilesystem::class,
        LegacyFilesystems::class,
        LegacyFilesystems::EVENT_REGISTER_FILESYSTEM_TYPES,
        Filesystems::class,
        'getAllFilesystemTypes',
        AdapterRetainedFilesystem::class,
        Local::class,
    ],
    'image transformers' => [
        ImageTransformers::class,
        AdapterRegistryImageTransformer::class,
        LegacyImageTransforms::class,
        LegacyImageTransforms::EVENT_REGISTER_IMAGE_TRANSFORMERS,
        LegacyImageTransforms::class,
        'getAllImageTransformers',
        AdapterRetainedImageTransformer::class,
        LegacyImageTransformer::class,
    ],
]);

abstract class AdapterRegistryElement extends Element
{
}

abstract class AdapterRegistryField extends Field
{
}

class AdapterRegistryAuthMethod extends TOTP
{
    public static function handle(): string
    {
        return 'adapter-registry';
    }
}

class AdapterRetainedAuthMethod extends TOTP
{
    public static function handle(): string
    {
        return 'adapter-retained';
    }
}

abstract class AdapterRegistryNestedEntryField extends Matrix
{
}

abstract class AdapterRetainedNestedEntryField extends Matrix
{
}

abstract class AdapterRegistryWidget extends Widget
{
}

abstract class AdapterRegistryFilesystem extends Local
{
}

abstract class AdapterRetainedFilesystem extends Local
{
}

abstract class AdapterRegistryImageTransformer implements ImageTransformerInterface
{
}

abstract class AdapterRetainedImageTransformer implements ImageTransformerInterface
{
}

class AdapterRegistryLink extends Url
{
    public static function id(): string
    {
        return 'adapterRegistry';
    }
}

class AdapterUrlRegistryLink extends Url
{
}
