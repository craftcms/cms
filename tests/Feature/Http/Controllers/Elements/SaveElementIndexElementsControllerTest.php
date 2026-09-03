<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\ElementTypes;
use CraftCms\Cms\Element\Operations\ElementPlaceholders;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\SaveElementIndexElementsController;
use CraftCms\Cms\Support\Facades\Fields as FieldsFacade;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\DB;

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
