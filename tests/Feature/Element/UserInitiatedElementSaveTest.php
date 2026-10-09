<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Models\Address as AddressModel;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementActivity;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\Elements as ElementsService;
use CraftCms\Cms\Element\ElementTypes;
use CraftCms\Cms\Element\Enums\ElementActivityType;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Exceptions\UnsupportedSiteException;
use CraftCms\Cms\Element\Operations\ElementPlaceholders;
use CraftCms\Cms\Element\UserInitiatedElementSave;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\Workflow\Models\Workflow;
use CraftCms\Cms\Workflow\Workflows;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->actor = User::factory()->admin()->create();
    actingAs($this->actor);

    $this->entryType = EntryType::factory()->create();
    $this->section = Section::factory()->withEntryTypes($this->entryType)->create([
        'handle' => 'user-initiated-save',
    ]);
});

it('saves approval-gated changes as an attributed draft', function () {
    $workflow = Workflow::query()->create([
        'name' => 'Editorial workflow',
        'uid' => Str::uuid7()->toString(),
    ]);
    $this->section->update(['workflowId' => $workflow->id]);
    Sections::refreshSections();

    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement(['title' => 'Canonical title']);
    $entry->title = 'Draft title';

    $result = app(UserInitiatedElementSave::class)->save($entry, $this->actor);

    expect($result->successful)->toBeTrue()
        ->and($result->element)->toBeInstanceOf(Entry::class)
        ->and($result->element->getIsDraft())->toBeTrue()
        ->and($result->element->draftCreatorId)->toBe($this->actor->id)
        ->and(Entry::find()->id($entry->id)->status(null)->one()->title)->toBe('Canonical title')
        ->and($result->element->title)->toBe('Draft title')
        ->and(DB::table(Table::ELEMENTACTIVITY)
            ->where('elementId', $entry->id)
            ->where('userId', $this->actor->id)
            ->where('draftId', $result->element->draftId)
            ->where('type', ElementActivityType::Save->value)
            ->exists())
        ->toBeTrue();
});

it('saves canonical changes with caller-selected cross-site validation and removes the actor provisional draft', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement(['title' => 'Canonical title']);
    app(Drafts::class)->createDraft($entry, $this->actor->id, provisional: true);
    $entry->title = 'Updated title';

    $elements = new class(app(ElementPlaceholders::class), app(ElementTypes::class), app(ElementCaches::class)) extends ElementsService
    {
        public ?bool $crossSiteValidate = null;

        #[Override]
        public function saveElement(
            ElementInterface $element,
            bool $runValidation = true,
            bool $propagate = true,
            ?bool $updateSearchIndex = null,
            bool $forceTouch = false,
            ?bool $crossSiteValidate = false,
            bool $saveContent = false,
        ): bool {
            $this->crossSiteValidate = $crossSiteValidate;

            return parent::saveElement(
                $element,
                $runValidation,
                $propagate,
                $updateSearchIndex,
                $forceTouch,
                $crossSiteValidate,
                $saveContent,
            );
        }
    };

    $userInitiatedSave = new UserInitiatedElementSave(
        app(Drafts::class),
        app(ElementActivity::class),
        $elements,
        app(Workflows::class),
    );

    $result = $userInitiatedSave->save($entry, $this->actor, crossSiteValidate: true);

    expect($result->successful)->toBeTrue()
        ->and($elements->crossSiteValidate)->toBeTrue()
        ->and(Entry::find()->id($entry->id)->status(null)->one()->title)->toBe('Updated title')
        ->and(Entry::find()
            ->drafts()
            ->provisionalDrafts()
            ->draftOf($entry->id)
            ->draftCreator($this->actor->id)
            ->status(null)
            ->count())
        ->toBe(0)
        ->and(DB::table(Table::ELEMENTACTIVITY)
            ->where('elementId', $entry->id)
            ->where('userId', $this->actor->id)
            ->where('type', ElementActivityType::Save->value)
            ->exists())
        ->toBeTrue();
});

it('uses live validation for an enabled element', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement(['title' => 'Canonical title']);
    $field = Field::factory()->create([
        'name' => 'Required body',
        'handle' => 'requiredBody',
        'type' => PlainText::class,
    ]);
    $fieldLayout = FieldLayout::create([
        'type' => Entry::class,
        'config' => [
            'tabs' => [[
                'uid' => Str::uuid()->toString(),
                'name' => 'Content',
                'elements' => [[
                    'uid' => Str::uuid()->toString(),
                    'type' => CustomField::class,
                    'fieldUid' => $field->uid,
                    'required' => true,
                ]],
            ]],
        ],
    ]);
    $this->entryType->update(['fieldLayoutId' => $fieldLayout->id]);
    EntryTypes::refreshEntryTypes();
    Fields::invalidateCaches();

    /** @var Entry $entry */
    $entry = Entry::find()->id($entry->id)->status(null)->one();
    $result = app(UserInitiatedElementSave::class)->save($entry, $this->actor);

    expect($result->successful)->toBeFalse()
        ->and($result->element->errors()->has('requiredBody'))->toBeTrue();
});

it('returns exception validation failures without leaking persistence exceptions', function (string $exceptionClass) {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement(['title' => 'Canonical title']);

    $elements = new class(app(ElementPlaceholders::class), app(ElementTypes::class), app(ElementCaches::class)) extends ElementsService
    {
        /** @var class-string<InvalidElementException|UnsupportedSiteException> */
        public string $exceptionClass;

        #[Override]
        public function saveElement(
            ElementInterface $element,
            bool $runValidation = true,
            bool $propagate = true,
            ?bool $updateSearchIndex = null,
            bool $forceTouch = false,
            ?bool $crossSiteValidate = false,
            bool $saveContent = false,
        ): bool {
            if ($this->exceptionClass === InvalidElementException::class) {
                $element->errors()->add('title', 'Invalid title.');

                throw new InvalidElementException($element);
            }

            throw new UnsupportedSiteException($element, 999999, 'Unsupported site.');
        }
    };
    $elements->exceptionClass = $exceptionClass;
    $userInitiatedSave = new UserInitiatedElementSave(
        app(Drafts::class),
        app(ElementActivity::class),
        $elements,
        app(Workflows::class),
    );

    $result = $userInitiatedSave->save($entry, $this->actor);

    expect($result->successful)->toBeFalse()
        ->and($result->element)->toBe($entry)
        ->and($result->element->errors()->all())->toBe(
            $exceptionClass === InvalidElementException::class
                ? ['Invalid title.']
                : ['Unsupported site.'],
        );
})->with([
    'invalid element' => InvalidElementException::class,
    'unsupported site' => UnsupportedSiteException::class,
]);

it('marks nested elements to update their owner search index before saving', function () {
    $address = AddressModel::factory()
        ->withOwnedElement($this->actor->asElement(), 1)
        ->createElement();

    $elements = new class(app(ElementPlaceholders::class), app(ElementTypes::class), app(ElementCaches::class)) extends ElementsService
    {
        public bool $updatesOwnerSearchIndex = false;

        #[Override]
        public function saveElement(
            ElementInterface $element,
            bool $runValidation = true,
            bool $propagate = true,
            ?bool $updateSearchIndex = null,
            bool $forceTouch = false,
            ?bool $crossSiteValidate = false,
            bool $saveContent = false,
        ): bool {
            $this->updatesOwnerSearchIndex = $element->updateSearchIndexForOwner;

            return parent::saveElement(
                $element,
                $runValidation,
                $propagate,
                $updateSearchIndex,
                $forceTouch,
                $crossSiteValidate,
                $saveContent,
            );
        }
    };
    $userInitiatedSave = new UserInitiatedElementSave(
        app(Drafts::class),
        app(ElementActivity::class),
        $elements,
        app(Workflows::class),
    );

    $userInitiatedSave->save($address, $this->actor);

    expect($elements->updatesOwnerSearchIndex)->toBeTrue();
});

it('rejects a concurrent save of the same existing element', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement(['title' => 'Canonical title']);
    $lock = Cache::lock("element:{$entry->id}", 15);
    expect($lock->get())->toBeTrue();

    try {
        expect(fn () => app(UserInitiatedElementSave::class)->save($entry, $this->actor))
            ->toThrow(LockTimeoutException::class);
    } finally {
        $lock->release();
    }

    expect(Entry::find()->id($entry->id)->status(null)->one()->title)->toBe('Canonical title');
});
