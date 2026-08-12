<?php

declare(strict_types=1);

use JMac\Testing\Matching\Argument;
use CraftCms\Cms\Cp\Html\ElementIndexHtml;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\DeletionBlockers\BaseDeletionBlocker;
use CraftCms\Cms\Element\DeletionBlockers\EntryAuthorsBlocker;
use CraftCms\Cms\Element\DeletionBlockers\FieldReferencesDeletionBlocker;
use CraftCms\Cms\Element\DeletionBlockers\RelationDeletionBlocker;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Events\DefineDeletionBlockers;
use CraftCms\Cms\Element\Jobs\ReplaceRelations;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Http\Controllers\Elements\DeleteElementsController;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

it('dispatches deletion blocker events with the default relation blocker', function () {
    $entry = EntryModel::factory()->createElement();

    Event::listen(function (DefineDeletionBlockers $event) use ($entry) {
        expect($event->elementType)->toBe(Entry::class)
            ->and($event->elements->ids()->all())->toBe([$entry->id])
            ->and($event->hardDelete)->toBeTrue()
            ->and($event->blockers)->toHaveCount(2);

        $blockerClasses = collect($event->blockers)->map(fn (object $blocker) => $blocker::class);

        expect($blockerClasses)
            ->toContain(RelationDeletionBlocker::class)
            ->toContain(FieldReferencesDeletionBlocker::class);

        $event->blockers[] = new TestDeletionBlocker(active: true);
    });

    $blockers = Entry::deletionBlockers(ElementCollection::make([$entry]), true);
    $blockerClasses = collect($blockers)->map(fn (object $blocker) => $blocker::class);

    expect($blockers)->toHaveCount(3)
        ->and($blockerClasses)
        ->toContain(RelationDeletionBlocker::class)
        ->toContain(FieldReferencesDeletionBlocker::class)
        ->toContain(TestDeletionBlocker::class);
});

it('reports entry author blockers with details and actions', function () {
    $author = UserModel::factory()->createElement();
    $entry = EntryModel::factory()
        ->hasAttached(UserModel::query()->find($author->id), ['sortOrder' => 0], 'authors')
        ->create();

    $this->mock(ElementIndexHtml::class, function (MockInterface $mock) use ($author) {
        $mock->expects('html')->with(Entry::class, Argument::satisfies(fn (array $config) => $config['context'] === 'pane' &&
                $config['sources'] === false &&
                $config['defaultTableColumns'] === [['authors'], ['section']] &&
                $config['defaultSort'] === ['section', 'asc'] &&
                $config['jsSettings']['criteria']['authorId'] === [$author->id] &&
                $config['jsSettings']['criteria']['status'] === null))->returns('<div>author details</div>');
    });

    $blocker = new EntryAuthorsBlocker(ElementCollection::make([$author]), false);
    $actions = $blocker->getActions();

    expect($blocker->isActive())->toBeTrue()
        ->and($blocker->getSummary())->toBe('1 entry has the user assigned as an author.')
        ->and($blocker->getDetails())->toBe('<div>author details</div>')
        ->and($actions)->toHaveCount(3)
        ->and(array_column($actions, 'label'))->toBe(['Reassign entry', 'Remove author from entry', 'Delete entry'])
        ->and($actions[0]['callback'])->toContain('entries/reassign-modal')
        ->and($actions[0]['callback'])->toContain((string) $author->id)
        ->and($actions[1]['callback'])->toContain('The entry will be updated once the user is deleted.')
        ->and($actions[2]['destructive'])->toBeTrue()
        ->and($actions[2]['callback'])->toContain('new Craft.ElementDeletionManager')
        ->and($actions[2]['callback'])->toContain((string) $entry->id);
});

it('counts unique entry author blockers across users and sites', function () {
    $authorA = UserModel::factory()->create();
    $authorB = UserModel::factory()->create();
    $secondSite = Site::factory()->create();

    $multiSiteEntry = EntryModel::factory()
        ->hasAttached($authorA, ['sortOrder' => 1], 'authors')
        ->hasAttached($authorB, ['sortOrder' => 2], 'authors')
        ->enabledForSites($secondSite)
        ->create();

    $blocker = new EntryAuthorsBlocker(ElementCollection::make([
        $authorA->asElement(),
        $authorB->asElement(),
    ]), false);
    $actions = $blocker->getActions();

    expect($blocker->isActive())->toBeTrue()
        ->and($blocker->getSummary())->toBe('1 entry has the users assigned as an author.')
        ->and(array_column($actions, 'label'))->toBe(['Reassign entry', 'Remove authors from entry', 'Delete entry'])
        ->and($actions[0]['callback'])->toContain((string) $authorA->id)
        ->and($actions[0]['callback'])->toContain((string) $authorB->id)
        ->and($actions[1]['callback'])->toContain('The entry will be updated once the users are deleted.')
        ->and($actions[2]['callback'])->toContain((string) $multiSiteEntry->id);
});

it('does not activate entry author blockers when the users have no entries', function () {
    $author = UserModel::factory()->createElement();

    $blocker = new EntryAuthorsBlocker(ElementCollection::make([$author]), false);

    expect($blocker->isActive())->toBeFalse()
        ->and($blocker->getSummary())->toBe('0 entries have the user assigned as an author.');
});

it('reports relation blockers with details and actions', function () {
    $field = Field::factory()->create([
        'handle' => 'relatedEntries',
        'type' => Entries::class,
    ]);
    $source = EntryModel::factory()->createElement();
    $target = EntryModel::factory()->createElement();

    DB::table(Table::RELATIONS)->insert([
        'fieldId' => $field->id,
        'sourceId' => $source->id,
        'sourceSiteId' => $source->siteId,
        'targetId' => $target->id,
        'sortOrder' => 1,
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => fake()->uuid(),
    ]);

    $this->mock(ElementIndexHtml::class, function (MockInterface $mock) {
        $mock->expects('html')->returns('<div>relation details</div>');
    });

    $blocker = new RelationDeletionBlocker(Entry::class, ElementCollection::make([$target]), true, [
        'elementIndexSettings' => [
            'defaultTableColumns' => [
                ['section'],
            ],
            'defaultSort' => ['section', 'asc'],
        ],
    ]);
    $actions = $blocker->getActions();

    expect($blocker->isActive())->toBeTrue()
        ->and($blocker->getSummary())->toBe('The entry is related by 1 other entry.')
        ->and($blocker->getDetails())->toBe('<div>relation details</div>')
        ->and($actions)->toHaveCount(2)
        ->and(array_column($actions, 'label'))->toBe(['Replace relation', 'Remove relation'])
        ->and($actions[0]['callback'])->toContain('delete-elements/replace-relations-modal')
        ->and($actions[0]['callback'])->toContain((string) $target->id)
        ->and($actions[1]['callback'])->toContain('The relation will be removed once the entry is deleted.');
});

it('does not activate relation blockers when there are no relations', function () {
    $target = EntryModel::factory()->createElement();

    $blocker = new RelationDeletionBlocker(Entry::class, ElementCollection::make([$target]), false);

    expect($blocker->isActive())->toBeFalse()
        ->and($blocker->getSummary())->toBe('The entry is related by 0 other entries.');
});

it('returns only active deletion blockers from the controller response', function () {
    $entry = EntryModel::factory()->createElement();

    Event::listen(function (DefineDeletionBlockers $event) {
        $event->blockers = [
            new TestDeletionBlocker(active: false, summary: 'Inactive blocker'),
            new TestDeletionBlocker(active: true, summary: 'Active blocker', details: '<p>Details</p>', actions: [
                ['label' => 'Resolve'],
            ]),
        ];
    });

    postJson(action([DeleteElementsController::class, 'deletionBlockers']), [
        'elementType' => Entry::class,
        'elementIds' => [$entry->id],
    ])
        ->assertOk()
        ->assertJsonPath('blockers.0.summary', 'Active blocker')
        ->assertJsonPath('blockers.0.details', '<p>Details</p>')
        ->assertJsonPath('blockers.0.actions.0.label', 'Resolve')
        ->assertJsonCount(1, 'blockers')
        ->assertJsonStructure(['elementPreview', 'headHtml', 'bodyHtml']);
});

it('queues relation replacement from the controller response', function () {
    Queue::fake();

    $field = Field::factory()->create([
        'handle' => 'relatedEntries',
        'type' => Entries::class,
    ]);
    $source = EntryModel::factory()->createElement();
    $oldTarget = EntryModel::factory()->createElement();
    $newTarget = EntryModel::factory()->createElement();

    DB::table(Table::RELATIONS)->insert([
        'fieldId' => $field->id,
        'sourceId' => $source->id,
        'sourceSiteId' => $source->siteId,
        'targetId' => $oldTarget->id,
        'sortOrder' => 1,
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => fake()->uuid(),
    ]);

    postJson(action([DeleteElementsController::class, 'replaceRelations']), [
        'elementType' => Entry::class,
        'elementIds' => [$oldTarget->id],
        'sourceElementType' => Entry::class,
        'newTargetId' => $newTarget->id,
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Relation queued to be replaced.');

    Queue::assertPushed(ReplaceRelations::class, fn ($job) => $job->sourceElementType === Entry::class &&
        $job->targetElementType === Entry::class &&
        $job->sourceIds === [$source->id] &&
        $job->oldTargetIds === [$oldTarget->id] &&
        $job->newTargetId === $newTarget->id);
});

class TestDeletionBlocker extends BaseDeletionBlocker
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
