<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\DeletionBlockers\BaseDeletionBlocker;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Elements as ElementsService;
use CraftCms\Cms\Element\ElementTypes;
use CraftCms\Cms\Element\Events\DefineDeletionBlockers;
use CraftCms\Cms\Element\Events\ElementDeleting;
use CraftCms\Cms\Element\Jobs\ReplaceRelations;
use CraftCms\Cms\Element\Operations\ElementPlaceholders;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Http\Controllers\Elements\DeleteElementsController;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

it('requires authentication', function () {
    Auth::logout();

    postJson(action([DeleteElementsController::class, 'deletionBlockers']))->assertUnauthorized();
    postJson(action([DeleteElementsController::class, 'destroy']))->assertUnauthorized();
    postJson(action([DeleteElementsController::class, 'replaceRelationsModal']))->assertUnauthorized();
    postJson(action([DeleteElementsController::class, 'replaceRelations']))->assertUnauthorized();
});

it('requires element ids for controller actions', function (string $method) {
    postJson(action([DeleteElementsController::class, $method]), [
        'elementType' => Entry::class,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('elementIds');
})->with([
    'deletionBlockers',
    'destroy',
    'replaceRelationsModal',
    'replaceRelations',
]);

describe('deletionBlockers', function () {
    it('returns only active blockers with element preview assets', function () {
        $entry = EntryModel::factory()->createElement();

        Event::listen(function (DefineDeletionBlockers $event) use ($entry) {
            expect($event->elementType)->toBe(Entry::class)
                ->and($event->elements->ids()->all())->toBe([$entry->id])
                ->and($event->hardDelete)->toBeTrue();

            $event->blockers = [
                new DeleteElementsControllerTestBlocker(active: false, summary: 'Inactive blocker'),
                new DeleteElementsControllerTestBlocker(active: true, summary: 'Active blocker', details: '<p>Details</p>', actions: [
                    ['label' => 'Resolve'],
                ]),
            ];
        });

        postJson(action([DeleteElementsController::class, 'deletionBlockers']), [
            'elementType' => Entry::class,
            'elementIds' => [$entry->id],
            'hardDelete' => true,
        ])->assertOk()
            ->assertJsonCount(1, 'blockers')
            ->assertJsonPath('totalElements', 1)
            ->assertJsonPath('blockers.0.summary', 'Active blocker')
            ->assertJsonPath('blockers.0.details', '<p>Details</p>')
            ->assertJsonPath('blockers.0.actions.0.label', 'Resolve')
            ->assertJsonStructure(['elementPreview', 'headHtml', 'bodyHtml']);
    });

    it('excludes nested elements owned by a non-primary owner from blockers', function () {
        $fixture = createDeleteElementsControllerNestedElementFixture();

        Event::listen(function (DefineDeletionBlockers $event) {
            expect($event->elements)->toHaveCount(0);

            $event->blockers = [
                new DeleteElementsControllerTestBlocker(active: true),
            ];
        });

        postJson(action([DeleteElementsController::class, 'deletionBlockers']), [
            'elementType' => Entry::class,
            'elementIds' => [$fixture['nested']->id],
            'ownerId' => $fixture['secondaryOwner']->id,
        ])->assertOk()
            ->assertJsonCount(1, 'blockers');
    });
});

describe('destroy', function () {
    it('soft deletes each selected element', function () {
        $first = EntryModel::factory()->createElement();
        $second = EntryModel::factory()->createElement();

        postJson(action([DeleteElementsController::class, 'destroy']), [
            'elementType' => Entry::class,
            'elementIds' => [$first->id, $second->id],
        ])->assertOk();

        expect(Entry::find()->id($first->id)->status(null)->trashed()->one()?->dateDeleted)->not->toBeNull()
            ->and(Entry::find()->id($second->id)->status(null)->trashed()->one()?->dateDeleted)->not->toBeNull();
    });

    it('hard deletes selected elements when requested', function () {
        $entry = EntryModel::factory()->createElement();

        postJson(action([DeleteElementsController::class, 'destroy']), [
            'elementType' => Entry::class,
            'elementIds' => [$entry->id],
            'hardDelete' => true,
        ])->assertOk();

        expect(DB::table(Table::ELEMENTS)->where('id', $entry->id)->exists())->toBeFalse();
    });

    it('includes descendants for soft deletes when requested', function () {
        $hierarchy = createStructureHierarchy();
        $deletedIds = [];
        $descendantIds = collect([$hierarchy['children'], $hierarchy['nested']])
            ->flatten()
            ->map(fn (Entry $entry) => $entry->id)
            ->all();

        app()->bind(ElementsService::class, function () use (&$deletedIds) {
            return new class(app(ElementPlaceholders::class), app(ElementTypes::class), app(ElementCaches::class), $deletedIds) extends ElementsService
            {
                public function __construct(
                    ElementPlaceholders $placeholders,
                    ElementTypes $elementTypes,
                    ElementCaches $elementCaches,
                    private array &$deletedIds,
                ) {
                    parent::__construct($placeholders, $elementTypes, $elementCaches);
                }

                public function deleteElement(ElementInterface $element, bool $hard = false): bool
                {
                    $this->deletedIds[] = $element->id;

                    return true;
                }
            };
        });

        postJson(action([DeleteElementsController::class, 'destroy']), [
            'elementType' => Entry::class,
            'elementIds' => [$hierarchy['root']->id],
            'withDescendants' => true,
        ])->assertOk();

        expect($deletedIds)->toContain($hierarchy['root']->id, ...$descendantIds);
    });

    it('skips elements the user cannot view or delete', function (string $blockedAbility) {
        $blocked = EntryModel::factory()->createElement();
        $allowed = EntryModel::factory()->createElement();

        Gate::before(function ($user, string $ability, array $arguments) use ($blocked, $blockedAbility) {
            if ($ability === $blockedAbility && ($arguments[0]->id ?? null) === $blocked->id) {
                return false;
            }

            return null;
        });

        postJson(action([DeleteElementsController::class, 'destroy']), [
            'elementType' => Entry::class,
            'elementIds' => [$blocked->id, $allowed->id],
        ])->assertOk();

        expect(Entry::find()->id($blocked->id)->status(null)->trashed()->exists())->toBeFalse()
            ->and(Entry::find()->id($allowed->id)->status(null)->trashed()->exists())->toBeTrue();
    })->with(['view', 'delete']);

    it('returns ok when an element deletion is cancelled', function () {
        $entry = EntryModel::factory()->createElement();

        Event::listen(ElementDeleting::class, function (ElementDeleting $event) use ($entry) {
            if ($event->element->id === $entry->id) {
                $event->isValid = false;
            }
        });

        postJson(action([DeleteElementsController::class, 'destroy']), [
            'elementType' => Entry::class,
            'elementIds' => [$entry->id],
        ])->assertOk();

        expect(Entry::find()->id($entry->id)->status(null)->trashed()->exists())->toBeFalse();
    });

});

describe('replaceRelationsModal', function () {
    it('requires a source element type', function () {
        $entry = EntryModel::factory()->createElement();

        postJson(action([DeleteElementsController::class, 'replaceRelationsModal']), [
            'elementType' => Entry::class,
            'elementIds' => [$entry->id],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('sourceElementType');
    });

    it('returns modal content for choosing the replacement target', function () {
        $entry = EntryModel::factory()->createElement();

        $response = post(action([DeleteElementsController::class, 'replaceRelationsModal']), [
            'elementType' => Entry::class,
            'elementIds' => [$entry->id],
            'hardDelete' => true,
            'sourceElementType' => Entry::class,
        ])->assertOk()
            ->assertJsonPath('action', 'delete-elements/replace-relations')
            ->assertJsonPath('submitButtonLabel', 'Replace')
            ->assertJson(fn ($json) => $json
                ->where('action', 'delete-elements/replace-relations')
                ->where('submitButtonLabel', 'Replace')
                ->where('formAttributes', [])
                ->where('errorSummary', null)
                ->has('namespace')
                ->has('headHtml')
                ->has('bodyHtml')
                ->has('deltaNames')
                ->has('initialDeltaValues')
                ->has('content'));

        expect($response->json('content'))->toContain(
            'elementType',
            'elementIds',
            'hardDelete',
            'sourceElementType',
            'delete-elements/replace-relations',
        );
    });
});

describe('replaceReferencesModal', function () {
    it('returns modal content with the selected target ids', function () {
        $entry = EntryModel::factory()->createElement();

        $response = post(action([DeleteElementsController::class, 'replaceReferencesModal']), [
            'elementType' => Entry::class,
            'elementIds' => [$entry->id],
            'hardDelete' => true,
        ])->assertOk()
            ->assertJsonPath('action', 'delete-elements/replace-references')
            ->assertJsonPath('submitButtonLabel', 'Replace');

        expect($response->json('content'))->toContain(
            'elementType',
            'elementIds',
            'hardDelete',
            'delete-elements/replace-references',
        );
    });
});

describe('replaceRelations', function () {
    it('requires a source element type and new target id', function () {
        $entry = EntryModel::factory()->createElement();

        postJson(action([DeleteElementsController::class, 'replaceRelations']), [
            'elementType' => Entry::class,
            'elementIds' => [$entry->id],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['sourceElementType', 'newTargetId']);
    });

    it('returns failure when the replacement target is empty', function () {
        $entry = EntryModel::factory()->createElement();

        postJson(action([DeleteElementsController::class, 'replaceRelations']), [
            'elementType' => Entry::class,
            'elementIds' => [$entry->id],
            'sourceElementType' => Entry::class,
            'newTargetId' => 0,
        ])->assertBadRequest()
            ->assertJsonPath('message', 'No new entry selected.');
    });

    it('queues relation replacement for all related source elements', function () {
        Queue::fake();

        $field = Field::factory()->create([
            'handle' => 'relatedEntries',
            'type' => Entries::class,
        ]);
        $firstSource = EntryModel::factory()->createElement();
        $secondSource = EntryModel::factory()->createElement();
        $oldTarget = EntryModel::factory()->createElement();
        $newTarget = EntryModel::factory()->createElement();

        DB::table(Table::RELATIONS)->insert([
            [
                'fieldId' => $field->id,
                'sourceId' => $firstSource->id,
                'sourceSiteId' => $firstSource->siteId,
                'targetId' => $oldTarget->id,
                'sortOrder' => 1,
                'dateCreated' => now(),
                'dateUpdated' => now(),
                'uid' => fake()->uuid(),
            ],
            [
                'fieldId' => $field->id,
                'sourceId' => $secondSource->id,
                'sourceSiteId' => $secondSource->siteId,
                'targetId' => $oldTarget->id,
                'sortOrder' => 1,
                'dateCreated' => now(),
                'dateUpdated' => now(),
                'uid' => fake()->uuid(),
            ],
        ]);

        postJson(action([DeleteElementsController::class, 'replaceRelations']), [
            'elementType' => Entry::class,
            'elementIds' => [$oldTarget->id],
            'sourceElementType' => Entry::class,
            'newTargetId' => $newTarget->id,
        ])->assertOk()
            ->assertJsonPath('message', 'Relations queued to be replaced.');

        Queue::assertPushed(ReplaceRelations::class, fn (ReplaceRelations $job) => $job->sourceElementType === Entry::class &&
            $job->targetElementType === Entry::class &&
            collect($job->sourceIds)->sort()->values()->all() === collect([$firstSource->id, $secondSource->id])->sort()->values()->all() &&
            $job->oldTargetIds === [$oldTarget->id] &&
            $job->newTargetId === $newTarget->id);
    });
});

describe('replaceReferences', function () {
    it('returns failure when the replacement target does not exist', function () {
        $entry = EntryModel::factory()->createElement();

        postJson(action([DeleteElementsController::class, 'replaceReferences']), [
            'elementType' => Entry::class,
            'elementIds' => [$entry->id],
            'newTargetId' => 999999,
        ])->assertBadRequest()
            ->assertJsonPath('message', 'The selected entry could not be found.');
    });
});

function createDeleteElementsControllerNestedElementFixture(): array
{
    $field = Field::factory()->create([
        'type' => ContentBlock::class,
    ]);

    Fields::refreshFields();

    $primaryOwner = EntryModel::factory()->createElement();
    $secondaryOwner = EntryModel::factory()->createElement();
    $nested = EntryModel::factory()->createElement([
        'primaryOwnerId' => $primaryOwner->id,
        'fieldId' => $field->id,
    ]);

    DB::table(Table::ELEMENTS_OWNERS)->insert([
        [
            'elementId' => $nested->id,
            'ownerId' => $primaryOwner->id,
            'sortOrder' => 1,
        ],
        [
            'elementId' => $nested->id,
            'ownerId' => $secondaryOwner->id,
            'sortOrder' => 2,
        ],
    ]);

    return [
        'field' => $field,
        'primaryOwner' => Elements::getElementById($primaryOwner->id),
        'secondaryOwner' => Elements::getElementById($secondaryOwner->id),
        'nested' => Entry::find()
            ->id($nested->id)
            ->ownerId($primaryOwner->id)
            ->status(null)
            ->one(),
    ];
}

class DeleteElementsControllerTestBlocker extends BaseDeletionBlocker
{
    public function __construct(
        private readonly bool $active,
        private readonly string $summary = 'Test blocker',
        private readonly ?string $details = null,
        private readonly array $actions = [],
    ) {
        parent::__construct(ElementCollection::make(), false);
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function getActions(): array
    {
        return $this->actions;
    }
}
