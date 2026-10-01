<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Address\Models\Address as AddressModel;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\ElementTypes;
use CraftCms\Cms\Element\Operations\ElementPlaceholders;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\SaveElementIndexElementsController;
use CraftCms\Cms\Support\Facades\Fields as FieldsFacade;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Conditions\EmailConditionRule;
use CraftCms\Cms\User\Conditions\UserCondition;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    $this->postSaveElements = fn (array $payload = []) => postJson(
        action(SaveElementIndexElementsController::class),
        array_merge([
            'elementType' => Entry::class,
        ], $payload),
    );
});

function embeddedInlineEditFixture(): array
{
    $text = Field::factory()->create(['handle' => 'innerText', 'type' => PlainText::class]);
    $lockedText = Field::factory()->create(['handle' => 'lockedText', 'type' => PlainText::class]);
    $editCondition = new UserCondition(User::class);
    $emailRule = $editCondition->createConditionRule(EmailConditionRule::class);
    $emailRule->operator = '=';
    $emailRule->value = 'nobody@example.test';
    $editCondition->addConditionRule($emailRule);
    $fieldLayout = FieldLayout::factory()->withContentTab([
        new CustomField(config: ['fieldUid' => $text->uid])->required(),
        new CustomField(config: ['fieldUid' => $lockedText->uid])->editCondition($editCondition),
    ])->create(['type' => Entry::class]);
    $nestedType = EntryType::factory()->withFieldLayout($fieldLayout)->create(['hasTitleField' => true]);
    $fixture = EntryModel::factory()
        ->withField('matrixField', Matrix::class, [
            'entryTypes' => [$nestedType->id],
            'viewMode' => Matrix::VIEW_MODE_INDEX,
        ])
        ->createElementWithFields();
    $owner = $fixture->element;
    $field = FieldsFacade::getFieldById($fixture->field('matrixField')->id);
    $uid = 'uid:'.Str::uuid();
    $owner->setFieldValueFromRequest('matrixField', [
        'entries' => [$uid => [
            'type' => $nestedType->handle,
            'title' => 'Canonical title',
            'fields' => ['innerText' => 'Canonical text'],
        ]],
        'sortOrder' => [$uid],
    ]);
    expect(app(Elements::class)->saveElement($owner))->toBeTrue();
    $owner = Entry::find()->id($owner->id)->one();
    $nested = $owner->getFieldValue('matrixField')->status(null)->one();
    $nested->setFieldValue('lockedText', 'Canonical locked text');
    expect(app(Elements::class)->saveElement($nested))->toBeTrue();
    $draft = app(Drafts::class)->createDraft($owner, auth()->id(), provisional: true);
    $control = $field->formControl(new FieldContext(path: 'matrixField', element: $draft));

    return compact('owner', 'field', 'nested', 'draft', 'control', 'lockedText');
}

it('requires authentication', function () {
    auth()->logout();

    postJson(action(SaveElementIndexElementsController::class), [
        'elementType' => Entry::class,
    ])->assertUnauthorized();
});

it('rejects requests without element data', function () {
    $entry = EntryModel::factory()->createElement();

    ($this->postSaveElements)([
        'siteId' => $entry->siteId,
        'namespace' => 'elementindex-test',
    ])->assertStatus(422)
        ->assertJsonPath('message', 'The elementindex-test field is required.');
});

it('rejects requests without valid element ids', function () {
    $entry = EntryModel::factory()->createElement();

    ($this->postSaveElements)([
        'siteId' => $entry->siteId,
        'namespace' => 'elementindex-test',
        'elementindex-test' => [
            'element-999999' => [
                'title' => 'After Save',
            ],
        ],
    ])->assertStatus(422)
        ->assertJsonPath('message', 'No valid element IDs provided.');
});

it('aggregates validation errors when saving inline-edited elements', function () {
    $field = Field::factory()->create([
        'handle' => 'requiredField',
        'type' => PlainText::class,
    ]);

    $firstEntry = EntryModel::factory()
        ->withFieldLayout(FieldLayout::factory()->forField($field, true))
        ->createElement();
    $secondEntry = EntryModel::factory()
        ->withFieldLayout(FieldLayout::factory()->forField($field, true))
        ->createElement();

    ($this->postSaveElements)([
        'siteId' => $firstEntry->siteId,
        'namespace' => 'elementindex-test',
        'elementindex-test' => [
            "element-$firstEntry->id" => [
                'fields' => [
                    'requiredField' => '',
                ],
            ],
            "element-$secondEntry->id" => [
                'fields' => [
                    'requiredField' => '',
                ],
            ],
        ],
    ])->assertOk()
        ->assertJsonPath("errors.$firstEntry->id.requiredField.0", fn (string $message) => $message !== '')
        ->assertJsonPath("errors.$secondEntry->id.requiredField.0", fn (string $message) => $message !== '');
});

it('saves inline-edited elements in a batch', function () {
    $firstEntry = EntryModel::factory()->createElement([
        'title' => 'First Before Save',
    ]);
    $secondEntry = EntryModel::factory()->createElement([
        'title' => 'Second Before Save',
    ]);

    ($this->postSaveElements)([
        'siteId' => $firstEntry->siteId,
        'namespace' => 'elementindex-test',
        'elementindex-test' => [
            "element-$firstEntry->id" => [
                'title' => 'First After Save',
            ],
            "element-$secondEntry->id" => [
                'title' => 'Second After Save',
            ],
        ],
    ])->assertOk();

    expect(Entry::find()->id($firstEntry->id)->status(null)->one()?->title)->toBe('First After Save')
        ->and(Entry::find()->id($secondEntry->id)->status(null)->one()?->title)->toBe('Second After Save');
});

it('saves embedded inline fields on an owner-specific clone without locked fields', function () {
    $fixture = embeddedInlineEditFixture();
    $manager = $fixture['control']->props()['manager'];

    ($this->postSaveElements)([
        ...$manager,
        'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
        'source' => '__IMP__',
        'baseCriteria' => ['ownerId' => 999999, 'fieldId' => 999999],
        'siteId' => $fixture['draft']->siteId,
        'namespace' => 'elementindex-test',
        'elementindex-test' => [
            "element-{$fixture['nested']->id}" => [
                'fields' => [
                    'innerText' => 'Draft text',
                    'lockedText' => 'Must not be saved',
                ],
            ],
        ],
    ])->assertOk()->assertJsonMissingPath('errors');

    $draftNested = Entry::find()
        ->fieldId($fixture['field']->id)
        ->ownerId($fixture['draft']->id)
        ->status(null)
        ->drafts(null)
        ->one();

    expect($draftNested->id)->not->toBe($fixture['nested']->id)
        ->and($draftNested->getFieldValue('innerText'))->toBe('Draft text')
        ->and($draftNested->getFieldValue('lockedText'))->toBe('Canonical locked text')
        ->and(Entry::find()->id($fixture['nested']->id)->status(null)->one()->getFieldValue('innerText'))->toBe('Canonical text');
});

it('keys embedded inline validation errors to the requested row', function () {
    $fixture = embeddedInlineEditFixture();
    $manager = $fixture['control']->props()['manager'];

    ($this->postSaveElements)([
        ...$manager,
        'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
        'source' => '__IMP__',
        'siteId' => $fixture['draft']->siteId,
        'namespace' => 'elementindex-test',
        'elementindex-test' => [
            "element-{$fixture['nested']->id}" => [
                'fields' => ['innerText' => ''],
            ],
        ],
    ])->assertOk()
        ->assertJsonPath("errors.{$fixture['nested']->id}.innerText.0", fn (string $message): bool => $message !== '');

    expect(Entry::find()
        ->fieldId($fixture['field']->id)
        ->ownerId($fixture['draft']->id)
        ->status(null)
        ->drafts(null)
        ->ids())
        ->toBe([$fixture['nested']->id]);
});

it('rejects an embedded inline selection outside the requested owner field', function () {
    $fixture = embeddedInlineEditFixture();
    $unrelated = embeddedInlineEditFixture();
    $manager = $fixture['control']->props()['manager'];

    ($this->postSaveElements)([
        ...$manager,
        'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
        'source' => '__IMP__',
        'siteId' => $fixture['draft']->siteId,
        'namespace' => 'elementindex-test',
        'elementindex-test' => [
            "element-{$unrelated['nested']->id}" => ['title' => 'Diverted'],
        ],
    ])->assertBadRequest();

    expect(Entry::find()->id($unrelated['nested']->id)->status(null)->one()->title)->toBe('Canonical title');
});

it('rejects embedded inline edits through an unscoped relation query', function () {
    $related = EntryModel::factory()->createElement(['title' => 'Related entry']);
    $fixture = EntryModel::factory()
        ->withField('relatedEntries', Entries::class, value: [$related->id])
        ->createElementWithFields();
    $owner = $fixture->element;
    $attribute = 'field:relatedEntries';
    SessionAuth::authorize("manageNestedElements::$owner->id::$attribute");
    $entryCount = Entry::find()->status(null)->count();

    ($this->postSaveElements)([
        'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
        'source' => '__IMP__',
        'ownerElementType' => Entry::class,
        'ownerId' => $owner->id,
        'ownerSiteId' => $owner->siteId,
        'attribute' => $attribute,
        'siteId' => $owner->siteId,
        'namespace' => 'elementindex-test',
        'elementindex-test' => [
            "element-$related->id" => ['title' => 'Must not be saved'],
        ],
    ])->assertBadRequest();

    expect(Entry::find()->id($related->id)->status(null)->one()->title)->toBe('Related entry')
        ->and(Entry::find()->status(null)->count())->toBe($entryCount);
});

it('accepts embedded inline edits through a generic owner-scoped nested query', function () {
    $owner = UserModel::factory()->createElement();
    $address = AddressModel::factory()->withOwnedElement($owner, 1)->createElement([
        'addressLine1' => 'Before save',
    ]);
    $manager = User::find()->id($owner->id)->one()->getAddressManager()->getIndexData($owner);

    ($this->postSaveElements)([
        ...$manager,
        'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
        'source' => '__IMP__',
        'elementType' => Address::class,
        'siteId' => $address->siteId,
        'namespace' => 'elementindex-test',
        'elementindex-test' => [
            "element-$address->id" => ['addressLine1' => 'After save'],
        ],
    ])->assertOk()->assertJsonMissingPath('errors');

    expect(Address::find()->id($address->id)->status(null)->one()->addressLine1)->toBe('After save');
});

it('ignores embedded ownership and derivative identity attributes from inline edits', function () {
    $fixture = embeddedInlineEditFixture();
    $unrelated = embeddedInlineEditFixture();
    $manager = $fixture['control']->props()['manager'];
    $postedUid = (string) Str::uuid();

    ($this->postSaveElements)([
        ...$manager,
        'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
        'source' => '__IMP__',
        'siteId' => $fixture['draft']->siteId,
        'namespace' => 'elementindex-test',
        'elementindex-test' => [
            "element-{$fixture['nested']->id}" => [
                'title' => 'Safely updated',
                'uid' => $postedUid,
                'canonicalId' => $unrelated['nested']->id,
                'draftId' => $unrelated['draft']->draftId,
                'revisionId' => 999999,
                'fieldId' => $unrelated['field']->id,
                'ownerId' => $unrelated['draft']->id,
                'primaryOwnerId' => $unrelated['draft']->id,
            ],
        ],
    ])->assertOk()->assertJsonMissingPath('errors');

    $draftNested = Entry::find()
        ->fieldId($fixture['field']->id)
        ->ownerId($fixture['draft']->id)
        ->status(null)
        ->drafts(null)
        ->one();

    expect($draftNested)->not->toBeNull()
        ->and($draftNested->title)->toBe('Safely updated')
        ->and($draftNested->uid)->not->toBe($postedUid)
        ->and($draftNested->getCanonicalId())->toBe($fixture['nested']->id)
        ->and($draftNested->draftId)->toBeNull()
        ->and($draftNested->revisionId)->toBeNull()
        ->and($draftNested->fieldId)->toBe($fixture['field']->id)
        ->and($draftNested->getOwnerId())->toBe($fixture['draft']->id)
        ->and($draftNested->getPrimaryOwnerId())->toBe($fixture['draft']->id)
        ->and(Entry::find()
            ->fieldId($unrelated['field']->id)
            ->ownerId($unrelated['draft']->id)
            ->status(null)
            ->drafts(null)
            ->count())
        ->toBe(1);
});

it('saves structured entry dates author IDs and cleared values', function () {
    $entry = EntryModel::factory()->createElement();
    $author = UserModel::factory()->admin()->createElement();

    ($this->postSaveElements)([
        'siteId' => $entry->siteId,
        'namespace' => 'elementindex-test',
        'elementindex-test' => [
            "element-$entry->id" => [
                'postDate' => ['date' => '2026-05-03', 'time' => '09:17', 'timezone' => 'Europe/Brussels'],
                'expiryDate' => ['date' => '', 'time' => '', 'timezone' => 'Europe/Brussels'],
                'authorIds' => [$author->id],
            ],
        ],
    ])->assertOk()->assertJsonMissingPath('errors');

    $saved = Entry::find()->id($entry->id)->status(null)->one();

    expect($saved->postDate->getTimestamp())->toBe(new DateTimeImmutable('2026-05-03T07:17:00+00:00')->getTimestamp())
        ->and($saved->expiryDate)->toBeNull()
        ->and($saved->getAuthorIds())->toBe([$author->id]);
});

it('saves and clears inline asset alt text', function () {
    $asset = AssetModel::factory()->createElement();

    foreach (["Description & <text>\nNext line", ''] as $alt) {
        postJson(action(SaveElementIndexElementsController::class), [
            'elementType' => Asset::class,
            'siteId' => $asset->siteId,
            'namespace' => 'elementindex-test',
            'elementindex-test' => ["element-$asset->id" => ['alt' => $alt]],
        ])->assertOk()->assertJsonMissingPath('errors');

        expect(Asset::find()->id($asset->id)->one()->alt)->toBe($alt === '' ? null : $alt);
    }
});

it('still authorizes saves before applying structured values', function () {
    $entry = EntryModel::factory()->createElement();
    $originalSlug = $entry->slug;
    Gate::before(fn ($user, $ability) => $ability === 'save' ? false : null);

    ($this->postSaveElements)([
        'siteId' => $entry->siteId,
        'namespace' => 'elementindex-test',
        'elementindex-test' => ["element-$entry->id" => ['slug' => 'changed']],
    ])->assertForbidden();

    expect(Entry::find()->id($entry->id)->status(null)->one()->slug)->toBe($originalSlug);
});

it('ignores sensitive attributes when saving user elements in a batch', function () {
    $field = Field::factory()->create([
        'name' => 'User Bio',
        'handle' => 'userBio',
        'type' => PlainText::class,
    ]);
    $fieldLayout = FieldLayout::factory()
        ->forField($field)
        ->create([
            'type' => User::class,
        ]);
    FieldsFacade::invalidateCaches();

    $editor = UserModel::factory()->admin()->createElement();
    $targetUser = UserModel::factory()->createElement();
    $originalPassword = DB::table(Table::USERS)
        ->where('id', $targetUser->id)
        ->value('password');
    $originalEmail = $targetUser->email;

    actingAs($editor);

    postJson(action(SaveElementIndexElementsController::class), [
        'elementType' => User::class,
        'siteId' => $targetUser->siteId,
        'namespace' => 'elementindex-test',
        'elementindex-test' => [
            "element-$targetUser->id" => [
                'email' => 'updated@example.com',
                'fields' => [
                    'userBio' => 'Updated bio',
                ],
                'newPassword' => 'SecurePassword123!',
            ],
        ],
    ])->assertOk();

    $updatedUser = User::find()
        ->id($targetUser->id)
        ->status(null)
        ->one();
    $userRecord = DB::table(Table::USERS)
        ->where('id', $targetUser->id)
        ->first();

    expect($userRecord->password)->toBe($originalPassword)
        ->and($userRecord->email)->toBe($originalEmail)
        ->and($updatedUser->getFieldValue('userBio'))->toBe('Updated bio');
});

it('rolls back prior saves when a later element fails', function () {
    $firstEntry = EntryModel::factory()->createElement([
        'title' => 'First Before Save',
    ]);
    $secondEntry = EntryModel::factory()->createElement([
        'title' => 'Second Before Save',
    ]);

    app()->instance(Elements::class, new class(app(ElementPlaceholders::class), app(ElementTypes::class), app(ElementCaches::class), $secondEntry->id) extends Elements
    {
        public function __construct(
            ElementPlaceholders $placeholders,
            ElementTypes $elementTypes,
            ElementCaches $elementCaches,
            private readonly int $failingElementId,
        ) {
            parent::__construct($placeholders, $elementTypes, $elementCaches);
        }

        public function saveElement(
            ElementInterface $element,
            bool $runValidation = true,
            bool $propagate = true,
            ?bool $updateSearchIndex = null,
            bool $forceTouch = false,
            ?bool $crossSiteValidate = false,
            bool $saveContent = false,
        ): bool {
            if ($element->id === $this->failingElementId) {
                return false;
            }

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
    });

    ($this->postSaveElements)([
        'siteId' => $firstEntry->siteId,
        'namespace' => 'elementindex-test',
        'elementindex-test' => [
            "element-$firstEntry->id" => [
                'title' => 'First After Save',
            ],
            "element-$secondEntry->id" => [
                'title' => 'Second After Save',
            ],
        ],
    ])->assertServerError();

    expect(Entry::find()->id($firstEntry->id)->status(null)->one()?->title)->toBe('First Before Save')
        ->and(Entry::find()->id($secondEntry->id)->status(null)->one()?->title)->toBe('Second Before Save');
});

it('preserves the legacy action route contract for save-elements', function () {
    $entry = EntryModel::factory()->createElement([
        'title' => 'Before Save',
    ]);

    postJson('/'.implode('/', array_filter([
        Cms::config()->cpTrigger,
        Cms::config()->actionTrigger,
        'element-indexes/save-elements',
    ])), [
        'elementType' => Entry::class,
        'siteId' => $entry->siteId,
        'namespace' => 'elementindex-test',
        'elementindex-test' => [
            "element-$entry->id" => [
                'title' => 'After Save',
            ],
        ],
    ])->assertOk();

    expect(Entry::find()->id($entry->id)->status(null)->one()?->title)->toBe('After Save');
});
