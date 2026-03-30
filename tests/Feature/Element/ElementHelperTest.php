<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Shared\Exceptions\OperationAbortedException;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

class TestFeatureElementHelperElement extends Element
{
    public ?string $uriFormat = null;

    #[Override]
    public static function displayName(): string
    {
        return 'Test Element';
    }

    #[Override]
    public function getUriFormat(): ?string
    {
        return $this->uriFormat;
    }
}

class TestFeatureOutdatedElement extends Element
{
    public ?Element $canonical = null;

    #[Override]
    public static function displayName(): string
    {
        return 'Test Outdated Element';
    }

    #[Override]
    public function getCanonical(bool $anySite = false): Element
    {
        return $this->canonical ?? $this;
    }
}

beforeEach(function () {
    actingAs(User::findOne());
});

function createElementHelperElement(array $attributes = []): TestFeatureElementHelperElement
{
    $element = new TestFeatureElementHelperElement;
    $element->siteId = Site::first()->id;

    foreach ($attributes as $key => $value) {
        $element->{$key} = $value;
    }

    return $element;
}

test('sets unique uri', function (array $expected, array $attributes) {
    $entry = createElementHelperElement($attributes);

    ElementHelper::setUniqueUri($entry);

    foreach ($expected as $key => $value) {
        expect($entry->{$key})->toBe($value);
    }
})->with([
    [['uri' => null], ['uriFormat' => null]],
    [['uri' => null], ['uriFormat' => '']],
    [['uri' => 'craft'], ['uriFormat' => '{slug}', 'slug' => 'craft']],
    [['uri' => 'test'], ['uriFormat' => 'test/{slug}']],
    [['uri' => 'test/test'], ['uriFormat' => 'test/{slug}', 'slug' => 'test']],
    [['uri' => 'test/tes.!@#$%^&*()_t'], ['uriFormat' => 'test/{slug}', 'slug' => 'tes.!@#$%^&*()_t']],
    [['uri' => 'different-uri/With--URL--1'], ['uriFormat' => 'different-uri/{slug}', 'slug' => 'With--URL--1']],
]);

test('set unique uri increments slug when uri already exists', function () {
    $existingElementId = Entry::factory()->create()->id;

    DB::table('elements_sites')
        ->where('elementId', $existingElementId)
        ->where('siteId', Site::first()->id)
        ->update([
            'slug' => 'With--URL--1',
            'uri' => 'some-uri/With--URL--1',
            'title' => 'Existing',
            'enabled' => true,
            'dateUpdated' => now(),
        ]);

    $entry = createElementHelperElement([
        'uriFormat' => 'some-uri/{slug}',
        'slug' => 'With--URL--1',
    ]);

    ElementHelper::setUniqueUri($entry);

    expect($entry->uri)->toBe('some-uri/With--URL--1-2');
});

test('set unique uri respects max slug increment', function () {
    Cms::config()->maxSlugIncrement = 0;

    $entry = createElementHelperElement([
        'uriFormat' => 'test/{slug}',
    ]);

    expect(fn () => ElementHelper::setUniqueUri($entry))->toThrow(OperationAbortedException::class);
});

test('set unique uri trims long slugs instead of failing', function () {
    $entry = createElementHelperElement([
        'uriFormat' => 'test/{slug}',
        'slug' => 'asdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadsssssssssssssssssssssssssssssssssssssssss22ssss',
    ]);

    ElementHelper::setUniqueUri($entry);

    expect($entry->uri)->toStartWith('test/')->and(mb_strlen((string) $entry->uri))->toBeLessThanOrEqual(255);
});

test('returns site statuses for unsaved enabled element', function () {
    $entry = Entry::factory()->createElement();
    $entry->id = null;
    $entry->enabled = true;
    $entry->setEnabledForSite(true);

    expect(ElementHelper::siteStatusesForElement($entry))->each->toBeTrue();
});

test('detects drafts revisions and outdated derivatives', function () {
    $entry = Entry::factory()->createElement();
    $draft = app(Drafts::class)->createDraft($entry);
    $revisionId = app(Revisions::class)->createRevision($entry);
    $revision = Craft::$app->getElements()->getElementById($revisionId, EntryElement::class, $entry->siteId);

    $canonical = new TestFeatureOutdatedElement;
    $canonical->id = 999;
    $canonical->dateUpdated = now();

    $derivative = new TestFeatureOutdatedElement;
    $derivative->id = 1000;
    $derivative->setCanonicalId($canonical->id);
    $derivative->canonical = $canonical;
    $derivative->dateCreated = now()->subDay();
    $derivative->dateLastMerged = now()->subDays(2);

    expect(ElementHelper::isDraft($draft))->toBeTrue()
        ->and(ElementHelper::isRevision($revision))->toBeTrue()
        ->and(ElementHelper::isDraftOrRevision($draft))->toBeTrue()
        ->and(ElementHelper::isDraftOrRevision($revision))->toBeTrue()
        ->and(ElementHelper::isOutdated($derivative))->toBeTrue();
});

test('builds editor and revisions urls', function () {
    $entry = Entry::factory()->createElement(['slug' => 'my-entry']);

    expect(ElementHelper::elementEditorUrl($entry, false))->toContain('/edit/'.$entry->getCanonicalId().'-my-entry')
        ->and(ElementHelper::elementRevisionsUrl($entry))->toContain('/revisions/'.$entry->getCanonicalId().'-my-entry')
        ->and(ElementHelper::postEditUrl($entry))->toBeString();
});

test('detects multisite elements', function () {
    $entry = Entry::factory()->createElement();
    Site::factory()->create();

    expect(ElementHelper::isMultiSite($entry))->toBeBool();
});
