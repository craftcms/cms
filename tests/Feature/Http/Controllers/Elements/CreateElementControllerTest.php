<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\DraftActivity;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Http\Controllers\Elements\CreateElementController;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\Fluent\AssertableJson;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    $this->entryType = EntryType::factory()->create();
    $this->section = Section::factory()->withEntryTypes($this->entryType)->create([
        'handle' => 'blog',
    ]);
});

function createElementControllerPayload(object $section, object $entryType, array $overrides = []): array
{
    return array_merge([
        'elementType' => Entry::class,
        'siteId' => Sites::getPrimarySite()->id,
        'sectionId' => $section->id,
        'typeId' => $entryType->id,
        'title' => 'New Draft Entry',
    ], $overrides);
}

it('requires authentication', function () {
    Auth::logout();

    postJson(action(CreateElementController::class), [
        'elementType' => Entry::class,
    ])->assertUnauthorized();
});

it('requires a valid element type', function () {
    postJson(action(CreateElementController::class), [
        'elementType' => stdClass::class,
    ])->assertBadRequest();
});

it('forbids creating an element when the user cannot save it', function () {
    $viewer = UserModel::factory()
        ->withPermissions([
            'accessCp',
            sprintf('editSite:%s', Sites::getPrimarySite()->uid),
        ])
        ->createElement();

    actingAs($viewer);

    postJson(action(CreateElementController::class), createElementControllerPayload($this->section, $this->entryType))
        ->assertForbidden();
});

it('returns a failure response when saving the draft fails', function () {
    app()->instance(Drafts::class, new readonly class(app(Elements::class), app(DraftActivity::class)) extends Drafts
    {
        public function saveElementAsDraft(
            ElementInterface $element,
            ?int $creatorId = null,
            ?string $name = null,
            ?string $notes = null,
            bool $markAsSaved = true,
        ): bool {
            return false;
        }
    });

    postJson(action(CreateElementController::class), createElementControllerPayload($this->section, $this->entryType))
        ->assertBadRequest()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('message', mb_ucfirst(t('Couldn’t create {type}.', [
                'type' => Entry::lowerDisplayName(),
            ])))
            ->where('modelName', 'element')
            ->where('element.title', 'New Draft Entry')
            ->etc()
        );
});

it('creates a draft and returns its control panel edit url for json requests', function () {
    $response = postJson(
        cp_url('actions/elements/create'),
        createElementControllerPayload($this->section, $this->entryType),
    )->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('message', t('{type} created.', ['type' => t('Draft')]))
            ->where('modelName', 'element')
            ->where('element.title', 'New Draft Entry')
            ->where('element.sectionId', $this->section->id)
            ->where('element.typeId', $this->entryType->id)
            ->where('element.draftId', fn (int $draftId) => $draftId > 0)
            ->etc()
        );

    /** @var Entry $draft */
    $draft = Entry::find()
        ->id($response->json('element.id'))
        ->drafts()
        ->status(null)
        ->one();

    expect($draft)->not->toBeNull()
        ->and($draft->draftId)->toBe($response->json('element.draftId'))
        ->and($draft->getIsUnpublishedDraft())->toBeTrue()
        ->and($draft->title)->toBe('New Draft Entry');
});

it('redirects to the draft edit page for non-json requests', function () {
    $draftCount = Entry::find()->drafts()->status(null)->count();

    post(cp_url('actions/elements/create'), createElementControllerPayload($this->section, $this->entryType))
        ->assertRedirect();

    expect(Entry::find()->drafts()->status(null)->count())->toBe($draftCount + 1);
});
