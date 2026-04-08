<?php

declare(strict_types=1);

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Exceptions\UnsupportedSiteException;
use CraftCms\Cms\Element\Models\ElementSiteSettings;
use CraftCms\Cms\Element\Operations\ElementUris;
use CraftCms\Cms\Element\Operations\ElementWrites;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Search\Search;
use CraftCms\Cms\Shared\Exceptions\OperationAbortedException;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->elements = instantiateWithoutConstructor(TestElements::class);
    $this->sites = instantiateWithoutConstructor(TestSites::class);
    $this->uris = instantiateWithoutConstructor(TestElementUris::class);
    $this->writes = instantiateWithoutConstructor(TestElementWrites::class);

    $this->executor = new TestPropagateElementWrites(
        $this->elements,
        $this->uris,
        Mockery::mock(ElementCaches::class),
        Mockery::mock(Search::class),
        $this->sites,
    );

    $this->primarySite = new Site([
        'id' => 1,
        'name' => 'Primary',
        'handle' => 'primary',
        'language' => 'en-US',
        'baseUrl' => 'https://example.test/',
        'uid' => 'primary-uid',
    ]);

    $this->secondarySite = new Site([
        'id' => 2,
        'name' => 'Secondary',
        'handle' => 'secondary',
        'language' => 'fr',
        'baseUrl' => 'https://example.test/fr/',
        'uid' => 'secondary-uid',
    ]);

    $this->sites->sitesById = [
        1 => $this->primarySite,
        2 => $this->secondarySite,
    ];
});

it('throws for unsupported sites', function () {
    $element = testElement();

    expect(fn () => $this->executor->propagate($element, [], 99))
        ->toThrow(UnsupportedSiteException::class, 'Attempting to propagate an element to an unsupported site.');
});

it('clones a new site element and saves it with propagated state', function () {
    $element = testElement();
    $element->id = 100;
    $element->siteId = 1;
    $element->title = 'Primary title';
    $element->slug = 'primary-slug';
    $element->uri = 'primary-uri';
    $element->dateCreated = now()->subDay();
    $element->dateUpdated = now();
    $element->enabled = true;
    $element->setEnabledForSite([
        1 => true,
        2 => false,
    ]);
    $element->setDirtyAttributes(['title', 'slug', 'uri', 'enabled']);

    $supportedSites = supportedSites();
    $siteElement = null;
    $siteSettingsRecord = new ElementSiteSettings;

    $result = $this->executor->propagate(
        $element,
        $supportedSites,
        2,
        $siteElement,
        crossSiteValidate: true,
        siteSettingsRecord: $siteSettingsRecord,
    );

    expect($result)->toBeTrue()
        ->and($siteElement)->toBeInstanceOf(TestElement::class)
        ->and($siteElement)->not->toBe($element)
        ->and($siteElement->siteId)->toBe(2)
        ->and($siteElement->siteSettingsId)->toBeNull()
        ->and($siteElement->isNewForSite)->toBeTrue()
        ->and($siteElement->title)->toBe('Primary title')
        ->and($siteElement->slug)->toBe('primary-slug')
        ->and($siteElement->dateCreated?->getTimestamp())->toBe($element->dateCreated?->getTimestamp())
        ->and($siteElement->dateUpdated?->getTimestamp())->toBe($element->dateUpdated?->getTimestamp())
        ->and($siteElement->getEnabledForSite())->toBeFalse()
        ->and($siteElement->propagating)->toBeTrue()
        ->and($siteElement->propagatingFrom)->toBe($element)
        ->and($siteElement->getScenario())->toBe(Element::SCENARIO_ESSENTIALS)
        ->and($siteElement->getDirtyAttributes())->toContain('enabled')
        ->and($element->newSiteIds)->toBe([2]);

    expect($this->elements->getElementByIdCalls)->toHaveCount(1)
        ->and($this->uris->setElementUriCalls)->toHaveCount(1)
        ->and($this->executor->saveCalls)->toHaveCount(1)
        ->and($this->executor->saveCalls[0]['siteElement'])->toBe($siteElement)
        ->and($this->executor->saveCalls[0]['runValidation'])->toBeTrue()
        ->and($this->executor->saveCalls[0]['crossSiteValidate'])->toBeFalse()
        ->and($this->executor->saveCalls[0]['propagate'])->toBeFalse()
        ->and($this->executor->saveCalls[0]['supportedSites'])->toBe($supportedSites)
        ->and($this->executor->saveCalls[0]['saveContent'])->toBeTrue()
        ->and($this->executor->saveCalls[0]['siteSettingsRecord'])->toBe($siteSettingsRecord);
});

it('preserves an existing site uri when propagateAll is enabled', function () {
    $element = testElement();
    $element->id = 101;
    $element->siteId = 1;
    $element->title = 'Primary title';
    $element->slug = 'primary-slug';
    $element->uri = 'primary-uri';
    $element->propagateAll = true;
    $element->setEnabledForSite([
        1 => true,
        2 => false,
    ]);

    $siteElement = testElement();
    $siteElement->id = 101;
    $siteElement->siteId = 2;
    $siteElement->uri = 'secondary-uri';
    $siteElement->title = 'Secondary title';
    $siteElement->slug = 'secondary-slug';
    $siteElement->setEnabledForSite(false);

    $originalSiteElement = $siteElement;

    $this->executor->propagate($element, supportedSites(), 2, $siteElement);

    expect($siteElement)->toBeInstanceOf(TestElement::class)
        ->and($siteElement)->not->toBe($originalSiteElement)
        ->and($siteElement->siteId)->toBe(2)
        ->and($siteElement->uri)->toBe('secondary-uri')
        ->and($siteElement->title)->toBe('Primary title')
        ->and($siteElement->slug)->toBe('primary-slug')
        ->and($siteElement->getEnabledForSite())->toBeFalse();
});

it('copies all field values for newly propagated sites', function () {
    $field = new TrackingField([
        'handle' => 'plainText',
        'name' => 'Plain Text',
    ]);
    $field->layoutElement = new CustomField($field);

    $element = testElement();
    $element->id = 102;
    $element->siteId = 1;
    $element->setFieldLayout(new TestFieldLayout([$field]));
    $element->setFieldValue('plainText', 'hello world');

    $siteElement = null;

    $this->executor->propagate($element, supportedSites(), 2, $siteElement, saveContent: true);

    expect($siteElement->getFieldValue('plainText'))->toBe('hello world');
});

it('propagates dirty fields with matching translation keys for existing sites', function () {
    $field = new TrackingField([
        'handle' => 'plainText',
        'name' => 'Plain Text',
    ]);
    $field->layoutElement = new CustomField($field, ['required' => false]);

    $element = testElement();
    $element->id = 103;
    $element->siteId = 1;
    $element->setFieldLayout(new TestFieldLayout([$field]));
    $element->setFieldValue('plainText', 'from primary');
    $element->setDirtyFields(['plainText']);

    $siteElement = testElement();
    $siteElement->id = 103;
    $siteElement->siteId = 2;
    $siteElement->setFieldLayout(new TestFieldLayout([$field]));
    $siteElement->setFieldValue('plainText', 'from secondary');
    $siteElement->isNewForSite = false;

    $this->executor->propagate($element, supportedSites(), 2, $siteElement, saveContent: true);

    expect($siteElement->getFieldValue('plainText'))->toBe('from primary')
        ->and($field->propagateCalls)->toHaveCount(1);
});

it('propagates required empty fields when propagateRequired is enabled', function () {
    $field = new TrackingField([
        'handle' => 'plainText',
        'name' => 'Plain Text',
    ]);
    $field->layoutElement = new CustomField($field, ['required' => true]);

    $element = testElement();
    $element->id = 104;
    $element->siteId = 1;
    $element->propagateRequired = true;
    $element->setFieldLayout(new TestFieldLayout([$field]));
    $element->setFieldValue('plainText', 'fallback value');

    $siteElement = testElement();
    $siteElement->id = 104;
    $siteElement->siteId = 2;
    $siteElement->setFieldLayout(new TestFieldLayout([$field]));
    $siteElement->setFieldValue('plainText', '');
    $siteElement->isNewForSite = false;

    $this->executor->propagate($element, supportedSites(), 2, $siteElement, saveContent: true);

    expect($siteElement->getFieldValue('plainText'))->toBe('fallback value')
        ->and($field->propagateCalls)->toHaveCount(1)
        ->and($siteElement->getScenario())->toBe(Element::SCENARIO_LIVE);
});

it('uses the live scenario when cross-site validation applies to enabled site elements', function () {
    $element = testElement();
    $element->id = 105;
    $element->siteId = 1;
    $element->setEnabledForSite([
        1 => true,
        2 => true,
    ]);

    $siteElement = null;

    $this->executor->propagate($element, supportedSites(enabledByDefault: true), 2, $siteElement, crossSiteValidate: true);

    expect($siteElement->getScenario())->toBe(Element::SCENARIO_LIVE);
});

it('continues when uri generation is aborted', function () {
    $this->uris->setElementUriException = new OperationAbortedException;

    $element = testElement();
    $element->id = 106;
    $element->siteId = 1;
    $element->uri = 'primary-uri';
    $element->setDirtyAttributes(['uri']);

    $siteElement = null;

    expect($this->executor->propagate($element, supportedSites(), 2, $siteElement))->toBeTrue()
        ->and($this->uris->setElementUriCalls)->toHaveCount(1);
});

it('adds a plain validation error when a propagated save fails', function () {
    $this->executor->returnValue = false;

    $element = testElement();
    $element->id = 107;
    $element->siteId = 1;

    $siteElement = testElement();
    $siteElement->id = 107;
    $siteElement->siteId = 2;
    $siteElement->errors()->add('title', 'Title is invalid');

    $result = $this->executor->propagate($element, supportedSites(), 2, $siteElement);

    expect($result)->toBeFalse()
        ->and($element->errors()->get('global'))->toHaveCount(1)
        ->and($element->errors()->first('global'))->toContain('Validation errors for site: “Secondary“');
});

it('adds a linked validation error when the current user can fix the propagated site', function () {
    Auth::setUser(new AuthorizedUser);
    swapUrlRequest('/admin/entries/100?foo=bar&site=primary');

    $this->executor->returnValue = false;
    $this->sites->isMultiSiteValue = true;

    $element = testElement();
    $element->id = 108;
    $element->siteId = 1;

    $siteElement = testElement();
    $siteElement->id = 108;
    $siteElement->siteId = 2;
    $siteElement->cpEditUrl = '/admin/entries/108';
    $siteElement->errors()->add('title', 'Title is invalid');
    $siteElement->canSave = true;

    $result = $this->executor->propagate($element, supportedSites(), 2, $siteElement);
    $message = $element->errors()->first('global');

    expect($result)->toBeFalse()
        ->and($message)->toContain('class="cross-site-validate"')
        ->and($message)->toContain('target="_blank"')
        ->and($message)->toContain(str_replace('&', '&amp;', Url::url('/admin/entries/108', ['foo' => 'bar', 'prevalidate' => 1])))
        ->and($message)->toContain('Validation errors for site: “Secondary“');
});

it('logs site errors and throws when the propagated save fails without validation messages', function () {
    Log::spy();

    $this->executor->returnValue = false;

    $element = testElement();
    $element->id = 109;
    $element->siteId = 1;

    $siteElement = testElement();
    $siteElement->id = 109;
    $siteElement->siteId = 2;

    expect(fn () => $this->executor->propagate($element, supportedSites(), 2, $siteElement))
        ->toThrow(Exception::class, 'Couldn’t propagate element to other site.');

    Log::shouldHaveReceived('error')->once()->with('Couldn’t propagate element to other site due to validation errors:');
});

function instantiateWithoutConstructor(string $class): object
{
    return new ReflectionClass($class)->newInstanceWithoutConstructor();
}

function supportedSites(bool $enabledByDefault = false): array
{
    return [
        1 => ['siteId' => 1, 'enabledByDefault' => true],
        2 => ['siteId' => 2, 'enabledByDefault' => $enabledByDefault],
    ];
}

function testElement(): TestElement
{
    $element = new TestElement;
    $element->markAsClean();

    return $element;
}

class TestElements extends Elements
{
    public ?Element $fetchedElement = null;

    public array $getElementByIdCalls = [];

    #[Override]
    public function getElementById(int $elementId, ?string $elementType = null, array|int|string|null $siteId = null, array $criteria = []): ?ElementInterface
    {
        $this->getElementByIdCalls[] = compact('elementId', 'elementType', 'siteId', 'criteria');

        return $this->fetchedElement;
    }
}

class TestSites extends Sites
{
    public array $sitesById = [];

    public bool $isMultiSiteValue = false;

    #[Override]
    public function getSiteById(int $siteId, ?bool $withDisabled = null): ?Site
    {
        return $this->sitesById[$siteId] ?? null;
    }

    #[Override]
    public function isMultiSite(bool $refresh = false, bool $withTrashed = false): bool
    {
        return $this->isMultiSiteValue;
    }
}

readonly class TestElementUris extends ElementUris
{
    public ?OperationAbortedException $setElementUriException = null;

    public array $setElementUriCalls = [];

    public function __construct() {}

    #[Override]
    public function setElementUri(ElementInterface $element): void
    {
        $this->setElementUriCalls[] = $element;

        if ($this->setElementUriException) {
            throw $this->setElementUriException;
        }

        $element->uri = sprintf('localized-%s', $element->siteId);
    }
}

readonly class TestElementWrites extends ElementWrites
{
    public function __construct() {}
}

readonly class TestPropagateElementWrites extends ElementWrites
{
    public bool $returnValue = true;

    public array $saveCalls = [];

    #[Override]
    protected function saveInternal(
        ElementInterface $element,
        bool $runValidation = true,
        bool $propagate = true,
        ?bool $updateSearchIndex = null,
        ?array $supportedSites = null,
        bool $forceTouch = false,
        bool $crossSiteValidate = false,
        bool $saveContent = false,
        ?ElementSiteSettings &$siteSettingsRecord = null,
        ?bool $inheritedUpdateSearchIndex = null,
    ): bool {
        $this->saveCalls[] = [
            'siteElement' => $element,
            'runValidation' => $runValidation,
            'propagate' => $propagate,
            'supportedSites' => $supportedSites,
            'crossSiteValidate' => $crossSiteValidate,
            'saveContent' => $saveContent,
            'siteSettingsRecord' => $siteSettingsRecord,
        ];

        return $this->returnValue;
    }
}

class TestElement extends Element
{
    #[Override]
    public bool $enabled = true;

    public ?string $cpEditUrl = null;

    public bool $canSave = true;

    private ?FieldLayout $fieldLayout = null;

    private array $fieldValues = [];

    #[Override]
    public static function displayName(): string
    {
        return 'Test Element';
    }

    #[Override]
    public static function hasTitles(): bool
    {
        return true;
    }

    #[Override]
    public static function hasUris(): bool
    {
        return true;
    }

    #[Override]
    public static function isLocalized(): bool
    {
        return true;
    }

    public function setFieldLayout(?FieldLayout $fieldLayout): void
    {
        $this->fieldLayout = $fieldLayout;
    }

    #[Override]
    public function getFieldLayout(): ?FieldLayout
    {
        return $this->fieldLayout;
    }

    #[Override]
    public function getFieldValue(string $fieldHandle): mixed
    {
        return $this->fieldValues[$fieldHandle] ?? null;
    }

    #[Override]
    public function setFieldValue(string $fieldHandle, mixed $value): void
    {
        $this->fieldValues[$fieldHandle] = $value;
    }

    #[Override]
    public function getTitleTranslationKey(): string
    {
        return 'shared-title';
    }

    #[Override]
    public function getSlugTranslationKey(): string
    {
        return 'shared-slug';
    }

    #[Override]
    public function getSite(): Site
    {
        return match ($this->siteId) {
            2 => new Site([
                'id' => 2,
                'name' => 'Secondary',
                'handle' => 'secondary',
                'language' => 'fr',
                'baseUrl' => 'https://example.test/fr/',
                'uid' => 'secondary-uid',
            ]),
            default => new Site([
                'id' => 1,
                'name' => 'Primary',
                'handle' => 'primary',
                'language' => 'en-US',
                'baseUrl' => 'https://example.test/',
                'uid' => 'primary-uid',
            ]),
        };
    }

    #[Override]
    public function canSave(User $user): bool
    {
        return $this->canSave;
    }

    #[Override]
    protected function cpEditUrl(): ?string
    {
        return $this->cpEditUrl;
    }
}

class TestFieldLayout extends FieldLayout
{
    public function __construct(private readonly array $customFields)
    {
        parent::__construct();
    }

    #[Override]
    public function getCustomFields(): array
    {
        return $this->customFields;
    }

    #[Override]
    public function getFieldByHandle(string $handle): ?FieldInterface
    {
        foreach ($this->customFields as $field) {
            if ($field->handle === $handle) {
                return $field;
            }
        }

        return null;
    }

    #[Override]
    public function getCustomFieldElements(): array
    {
        return array_map(fn (Field $field) => $field->layoutElement, $this->customFields);
    }
}

class TrackingField extends Field
{
    public array $propagateCalls = [];

    #[Override]
    public function propagateValue(ElementInterface $from, ElementInterface $to): void
    {
        $this->propagateCalls[] = [
            'from' => $from,
            'to' => $to,
        ];

        parent::propagateValue($from, $to);
    }
}

class AuthorizedUser extends User
{
    #[Override]
    public function can($abilities, $arguments = []): bool
    {
        return $abilities === 'editSite:secondary-uid';
    }
}
