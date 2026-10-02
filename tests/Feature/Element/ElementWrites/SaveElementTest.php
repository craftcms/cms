<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Element\Events\ElementLifecyclePropagated;
use CraftCms\Cms\Element\Events\ElementSaved;
use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\Element\Events\ElementSearchIndexUpdating;
use CraftCms\Cms\Element\Exceptions\UnsupportedSiteException;
use CraftCms\Cms\Element\Operations\ElementCanonicalChanges;
use CraftCms\Cms\Element\Operations\ElementDuplicates;
use CraftCms\Cms\Element\Operations\ElementWrites;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Queries\Exceptions\ElementNotFoundException;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\Models\FieldLayout as FieldLayoutModel;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\TestClasses\TestEntryWithAfterValidate;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

class TestSaveElementActionElement extends Element
{
    public bool $beforeSaveCalled = false;

    public bool $afterSaveCalled = false;

    public bool $afterPropagateCalled = false;

    public ?Closure $beforePropagation = null;

    public ?FieldLayout $mockFieldLayout = null;

    public ?string $mockCpEditUrl = null;

    public bool $returnFalseFromBeforeSave = false;

    #[Override]
    public static function displayName(): string
    {
        return 'Test Save Element';
    }

    #[Override]
    public static function find(): ElementQuery
    {
        return new ElementQuery(static::class);
    }

    #[Override]
    public static function hasTitles(): bool
    {
        return true;
    }

    #[Override]
    public static function trackChanges(): bool
    {
        return true;
    }

    #[Override]
    public function getSupportedSites(): array
    {
        return [
            [
                'siteId' => Sites::getPrimarySite()->id,
                'propagate' => true,
                'enabledByDefault' => true,
            ],
        ];
    }

    #[Override]
    public function getFieldLayout(): ?FieldLayout
    {
        return $this->mockFieldLayout ??= new FieldLayout([
            'type' => static::class,
        ]);
    }

    #[Override]
    protected function cpEditUrl(): ?string
    {
        return $this->mockCpEditUrl;
    }

    #[Override]
    public function beforeSave(bool $isNew): bool
    {
        $this->beforeSaveCalled = true;

        if ($this->returnFalseFromBeforeSave) {
            return false;
        }

        return parent::beforeSave($isNew);
    }

    #[Override]
    public function afterSave(bool $isNew): void
    {
        $this->afterSaveCalled = true;

        parent::afterSave($isNew);
    }

    #[Override]
    public function afterPropagate(bool $isNew): void
    {
        $this->afterPropagateCalled = true;
        if ($this->beforePropagation) {
            ($this->beforePropagation)();
        }

        parent::afterPropagate($isNew);
    }
}

class TestLocalizedSaveElementActionElement extends TestSaveElementActionElement
{
    #[Override]
    public static function isLocalized(): bool
    {
        return true;
    }

    #[Override]
    public function getSupportedSites(): array
    {
        return collect(Sites::getAllSites(true))
            ->map(fn ($site) => [
                'siteId' => $site->id,
                'propagate' => true,
                'enabledByDefault' => true,
            ])
            ->all();
    }
}

beforeEach(function () {
    actingAs(User::findOne());

    $this->writes = app(ElementWrites::class);
});

function createEntryWithPlainTextField(array $entryAttributes = []): array
{
    $result = EntryModel::factory()
        ->withField('bodyField', PlainText::class)
        ->createElementWithFields($entryAttributes, save: false);

    /** @var Entry $entry */
    $entry = $result->element;
    $field = $result->field('bodyField');
    $fieldLayout = $entry->getFieldLayout();

    return [$entry, $field, $fieldLayout];
}

it('returns false when ElementSaving vetoes the save', function () {
    $element = new TestSaveElementActionElement;
    $element->siteId = Sites::getPrimarySite()->id;
    $element->title = 'Blocked element';

    Event::listen(function (ElementSaving $event) use ($element) {
        if ($event->element !== $element) {
            return;
        }

        $event->isValid = false;
    });

    $afterSaveTriggered = false;
    Event::listen(function (ElementSaved $event) use ($element, &$afterSaveTriggered) {
        if ($event->element === $element) {
            $afterSaveTriggered = true;
        }
    });

    expect($this->writes->saveElement($element))->toBeFalse()
        ->and($element->id)->toBeNull()
        ->and($element->beforeSaveCalled)->toBeFalse()
        ->and($afterSaveTriggered)->toBeFalse()
        ->and(DB::table(Table::ELEMENTS)->count())->toBe(1);
});

it('returns false when the element beforeSave hook vetoes the save', function () {
    $element = new TestSaveElementActionElement;
    $element->siteId = Sites::getPrimarySite()->id;
    $element->title = 'Blocked by hook';
    $element->propagateAll = true;
    $element->firstSave = true;
    $element->isNewForSite = true;
    $element->returnFalseFromBeforeSave = true;

    expect($this->writes->saveElement($element))->toBeFalse()
        ->and($element->id)->toBeNull()
        ->and($element->beforeSaveCalled)->toBeTrue()
        ->and($element->propagateAll)->toBeTrue()
        ->and($element->firstSave)->toBeTrue()
        ->and($element->isNewForSite)->toBeTrue();
});

it('throws for unsupported sites and resets transient flags', function () {
    $element = new TestSaveElementActionElement;
    $element->siteId = Sites::getPrimarySite()->id + 999;
    $element->title = 'Wrong site';
    $element->propagateAll = true;
    $element->firstSave = true;
    $element->isNewForSite = true;

    expect(fn () => $this->writes->saveElement($element))
        ->toThrow(UnsupportedSiteException::class);

    expect($element->id)->toBeNull()
        ->and($element->propagateAll)->toBeTrue()
        ->and($element->firstSave)->toBeTrue()
        ->and($element->isNewForSite)->toBeTrue();
});

it('assigns a default title when validation is skipped and title is invalid', function () {
    $entry = EntryModel::factory()->createElement();
    $entry->title = str_repeat('a', 256);

    expect($this->writes->saveElement($entry, runValidation: false))->toBeTrue();

    $savedEntry = entryQuery()->id($entry->id)->firstOrFail();

    expect($savedEntry->title)->toBe("Entry {$entry->id}")
        ->and($savedEntry->errors()->isEmpty())->toBeTrue();
});

it('returns false when validation fails and does not dispatch ElementSaved', function () {
    $entry = EntryModel::factory()->createElement();
    $entry->title = str_repeat('a', 256);

    $afterSaveTriggered = false;
    Event::listen(function (ElementSaved $event) use ($entry, &$afterSaveTriggered) {
        if ($event->element->id === $entry->id) {
            $afterSaveTriggered = true;
        }
    });

    expect($this->writes->saveElement($entry))->toBeFalse()
        ->and($entry->errors()->has('title'))->toBeTrue()
        ->and($afterSaveTriggered)->toBeFalse();
});

it('throws when saving an existing element ID that does not exist', function () {
    $element = new TestSaveElementActionElement;
    $element->id = 999999;
    $element->siteId = Sites::getPrimarySite()->id;
    $element->title = 'Missing element';

    expect(fn () => $this->writes->saveElement($element))
        ->toThrow(ElementNotFoundException::class, "No element exists with the ID '999999'");
});

it('persists custom field content and records changed attributes and fields', function () {
    [$entry, $field, $fieldLayout] = createEntryWithPlainTextField(['title' => 'Original title']);

    $entry->title = 'Updated title';
    $entry->setFieldValue($field->handle, 'Updated body');

    expect($this->writes->saveElement($entry, updateSearchIndex: false))->toBeTrue();

    $siteSettings = DB::table(Table::ELEMENTS_SITES)
        ->where('elementId', $entry->id)
        ->where('siteId', $entry->siteId)
        ->first();

    $content = json_decode((string) $siteSettings->content, true, 512, JSON_THROW_ON_ERROR);
    $layoutElementUid = $fieldLayout->getCustomFieldElements()[0]->uid;

    expect($content[$layoutElementUid])->toBe('Updated body')
        ->and(DB::table(Table::CHANGEDATTRIBUTES)
            ->where('elementId', $entry->id)
            ->where('siteId', $entry->siteId)
            ->where('attribute', 'title')
            ->exists())->toBeTrue()
        ->and(DB::table(Table::CHANGEDFIELDS)
            ->where('elementId', $entry->id)
            ->where('siteId', $entry->siteId)
            ->where('fieldId', $field->id)
            ->where('layoutElementUid', $layoutElementUid)
            ->exists())->toBeTrue();
});

it('queues search index updates for searchable dirty fields', function () {
    [$entry, $field] = createEntryWithPlainTextField(['title' => 'Searchable entry']);

    $field->update(['searchable' => true]);
    Fields::invalidateCaches();
    Fields::refreshFields();

    $entry = entryQuery()->id($entry->id)->firstOrFail();
    $entry->updateSearchIndexImmediately = false;
    $entry->setFieldValue($field->handle, 'Search me');

    $beforeUpdateTriggered = false;
    Event::listen(function (ElementSearchIndexUpdating $event) use ($entry, &$beforeUpdateTriggered) {
        if ($event->element->id === $entry->id) {
            $beforeUpdateTriggered = true;
        }
    });

    Config::set('queue.default', 'null');

    expect($this->writes->saveElement($entry))->toBeTrue()
        ->and($beforeUpdateTriggered)->toBeTrue()
        ->and(DB::table(Table::SEARCHINDEXQUEUE)
            ->where('elementId', $entry->id)
            ->where('siteId', $entry->siteId)
            ->exists())->toBeTrue()
        ->and(DB::table(Table::SEARCHINDEXQUEUE_FIELDS)
            ->where('fieldHandle', $field->handle)
            ->exists())->toBeTrue();
});

it('can cancel search index updates with ElementSearchIndexUpdating', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Before search event']);
    $entry->title = 'Changed title';

    Event::listen(function (ElementSearchIndexUpdating $event) use ($entry) {
        if ($event->element->id === $entry->id) {
            $event->isValid = false;
        }
    });

    expect($this->writes->saveElement($entry))->toBeTrue()
        ->and(DB::table(Table::SEARCHINDEXQUEUE)
            ->where('elementId', $entry->id)
            ->exists())->toBeFalse();
});

it('fires ElementSaved and marks the element clean after a successful save', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Initial title']);
    $entry->title = 'Saved title';

    Event::fake([ElementSaved::class]);

    expect($this->writes->saveElement($entry, updateSearchIndex: false))->toBeTrue()
        ->and($entry->getDirtyAttributes())->toBeEmpty()
        ->and($entry->getDirtyFields())->toBeEmpty();

    Event::assertDispatched(fn (ElementSaved $event): bool => $event->element->id === $entry->id && $event->isNew === false);
});

it('preserves the caller scenario after save', function (?string $scenario) {
    $entry = EntryModel::factory()->createElement(['title' => 'Initial title']);

    if ($scenario !== null) {
        $entry->ruleset->useScenario($scenario);
    }

    $entry->title = 'Saved title';

    expect($this->writes->saveElement($entry, updateSearchIndex: false))->toBeTrue()
        ->and($entry->ruleset->getScenario())->toBe($scenario ?? ElementRules::SCENARIO_DEFAULT);
})->with([
    'default scenario' => [null],
    'live scenario' => [ElementRules::SCENARIO_LIVE],
    'essentials scenario' => [ElementRules::SCENARIO_ESSENTIALS],
]);

it('enables the current site when a single-site element is disabled for that site', function () {
    $element = new TestSaveElementActionElement;
    $element->siteId = Sites::getPrimarySite()->id;
    $element->title = 'Single-site element';
    $element->enabled = true;
    $element->setEnabledForSite(false);

    expect($this->writes->saveElement($element, updateSearchIndex: false))->toBeTrue()
        ->and($element->enabled)->toBeFalse()
        ->and($element->getEnabledForSite())->toBeTrue();
});

it('saves a new localized element across all supported sites', function (bool $reentrant) {
    Site::factory()->create();
    Sites::refreshSites();

    $element = new TestLocalizedSaveElementActionElement;
    $element->siteId = Sites::getPrimarySite()->id;
    $element->title = 'Localized element';
    $element->getFieldLayout()->setGeneratedFields([
        ['uid' => 'generated', 'handle' => 'siteTitle', 'template' => '{{ object.title }} {{ object.siteId }}'],
    ]);
    if ($reentrant) {
        $element->beforePropagation = function () use ($element) {
            $element->beforePropagation = null;
            $this->writes->saveElement($element, propagate: false, updateSearchIndex: false);
        };
    }

    expect($this->writes->saveElement($element, updateSearchIndex: false))->toBeTrue()
        ->and($element->afterSaveCalled)->toBeTrue()
        ->and($element->afterPropagateCalled)->toBeTrue()
        ->and(DB::table(Table::ELEMENTS_SITES)
            ->where('elementId', $element->id)
            ->count())->toBe(2)
        ->and($element->newSiteIds)->toBeEmpty();

    foreach (DB::table(Table::ELEMENTS_SITES)->where('elementId', $element->id)->get() as $record) {
        expect(json_decode($record->content, true)['generated'])->toBe("Localized element $record->siteId");
    }
})->with(['ordinary' => false, 'reentrant' => true]);

it('stores generated field values after save', function () {
    $listeners = count(Event::getListeners(ElementLifecyclePropagated::class));
    $element = new TestSaveElementActionElement;
    $element->siteId = Sites::getPrimarySite()->id;
    $element->title = 'Generated element';
    $element->mockFieldLayout = new FieldLayout([
        'type' => TestSaveElementActionElement::class,
    ]);
    $element->mockFieldLayout->setGeneratedFields([
        [
            'uid' => 'generated-field-uid',
            'name' => 'Generated Field',
            'handle' => 'generatedField',
            'template' => '{{ object.title }}',
        ],
    ]);
    $element->setDirtyAttributes(['title']);

    expect($this->writes->saveElement($element, updateSearchIndex: false))->toBeTrue()
        ->and($element->getGeneratedFieldValues())->toBe(['generatedField' => 'Generated element']);

    $content = DB::table(Table::ELEMENTS_SITES)
        ->where('elementId', $element->id)
        ->where('siteId', $element->siteId)
        ->value('content');

    expect(json_decode((string) $content, true, 512, JSON_THROW_ON_ERROR))
        ->toMatchArray(['generated-field-uid' => 'Generated element']);

    $element->title = 'Rolled back';
    $element->beforePropagation = fn () => throw new RuntimeException('Propagation failed');
    expect(fn () => $this->writes->saveElement($element, updateSearchIndex: false))
        ->toThrow(RuntimeException::class, 'Propagation failed');
    expect(DB::table(Table::ELEMENTS_SITES)->where('id', $element->siteSettingsId)->value('content'))
        ->toBe($content);

    $latest = TestSaveElementActionElement::find()->id($element->id)->one();
    $latest->mockFieldLayout = clone $element->mockFieldLayout;
    $latest->mockFieldLayout->setGeneratedFields([
        ['uid' => 'generated-field-uid', 'handle' => 'latestValue', 'template' => 'Updated {{ object.title }}'],
    ]);
    Event::listen(ElementLifecyclePropagated::class, function ($event) use ($latest) {
        expect($event->element)->toBe($latest)
            ->and($latest->getGeneratedFieldValues())->toBe(['latestValue' => 'Updated Generated element']);
    });

    expect($this->writes->saveElement($latest, updateSearchIndex: false))->toBeTrue()
        ->and(count(Event::getListeners(ElementLifecyclePropagated::class)))->toBe($listeners + 1);
});

it('completes nested generated values across localized saves, duplicates, and merges', function () {
    $secondary = Site::factory()->create();
    Sites::refreshSites();
    $blockType = EntryType::factory()->create(['hasTitleField' => true]);
    $matrix = Field::factory()->create([
        'handle' => 'blocks', 'type' => Matrix::class, 'settings' => ['entryTypes' => [$blockType->id]],
    ]);
    $type = EntryType::factory()->withFieldLayout(FieldLayoutModel::factory()->forField($matrix))->create();
    $section = Section::factory()->withEntryTypes($type)->withSites($secondary)->create([
        'propagationMethod' => PropagationMethod::All, 'enableVersioning' => false,
    ]);
    $entry = EntryModel::factory()->forSection($section)->forEntryType($type)->createElement(['title' => 'Original']);
    $layout = $entry->getFieldLayout();
    $layout->setGeneratedFields([
        ['uid' => 'generated', 'handle' => 'summary', 'template' => '{{ object.title }} {{ object.siteId }} {{ object.blocks.count() }}'],
    ]);
    Fields::saveLayout($layout);
    $uid = (string) str()->uuid();
    $entry->setFieldValueFromRequest('blocks', [
        'entries' => ["uid:$uid" => ['type' => $blockType->handle, 'title' => 'Block', 'enabled' => true]],
        'sortOrder' => [$uid],
    ]);
    expect($this->writes->saveElement($entry))->toBeTrue();
    $duplicate = app(ElementDuplicates::class)->duplicateElement($entry);

    foreach ([$entry, $duplicate] as $element) {
        $records = DB::table(Table::ELEMENTS_SITES)->where('elementId', $element->id)->get();
        expect($records)->toHaveCount(2);
        foreach ($records as $record) {
            expect(json_decode($record->content, true)['generated'])->toBe("Original $record->siteId 1");
        }
    }

    $draft = app(Drafts::class)->createDraft($entry, User::findOne()->id);
    foreach ($entry->getLocalizedQuery()->siteId('*')->all() as $localized) {
        $localized->title = 'Updated';
        $this->writes->saveElement($localized, propagate: false);
    }
    app(ElementCanonicalChanges::class)->mergeCanonicalChanges($draft);

    foreach (DB::table(Table::ELEMENTS_SITES)->where('elementId', $draft->id)->get() as $record) {
        expect(json_decode($record->content, true)['generated'])->toBe("Updated $record->siteId 1");
    }
});

it('returns false when an afterValidate hook adds errors during save validation', function () {
    $baseEntry = EntryModel::factory()->createElement(['title' => 'Existing entry']);

    $entry = new TestEntryWithAfterValidate;
    $entry->id = $baseEntry->id;
    $entry->siteId = $baseEntry->siteId;
    $entry->siteSettingsId = $baseEntry->siteSettingsId;
    $entry->sectionId = $baseEntry->sectionId;
    $entry->typeId = $baseEntry->typeId;
    $entry->fieldLayoutId = $baseEntry->fieldLayoutId;
    $entry->enabled = $baseEntry->enabled;
    $entry->slug = $baseEntry->slug;
    $entry->uri = $baseEntry->uri;
    $entry->postDate = $baseEntry->postDate;
    $entry->dateCreated = $baseEntry->dateCreated;
    $entry->dateUpdated = $baseEntry->dateUpdated;
    $entry->title = 'Updated entry';

    expect($this->writes->saveElement($entry))->toBeFalse()
        ->and($entry->afterValidateCalled)->toBeTrue()
        ->and($entry->errors()->has('customError'))->toBeTrue()
        ->and(entryQuery()->id($baseEntry->id)->firstOrFail()->title)->toBe('Existing entry');
});
