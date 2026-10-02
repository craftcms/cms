<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Actions\Delete;
use CraftCms\Cms\Element\Actions\Duplicate;
use CraftCms\Cms\Element\Actions\ElementAction;
use CraftCms\Cms\Element\Actions\SetStatus;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\Events\ElementActionsResolving;
use CraftCms\Cms\Element\Events\ElementDeleting;
use CraftCms\Cms\Element\Exporters\Raw;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Http\Controllers\Elements\PerformElementActionController;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    $this->performElementAction = fn (array $payload = []) => postJson(
        action(PerformElementActionController::class),
        array_merge([
            'context' => 'index',
            'source' => '*',
            'viewState' => [
                'mode' => 'table',
                'static' => false,
            ],
        ], $payload),
    );
});

function embeddedActionFixture(array $titles = ['Nested entry'], ?int $maxEntries = null): array
{
    $nestedType = EntryType::factory()->withFieldLayout()->create(['hasTitleField' => true]);
    $fixture = EntryModel::factory()
        ->withField('matrixField', Matrix::class, [
            'entryTypes' => [$nestedType->id],
            'viewMode' => Matrix::VIEW_MODE_INDEX,
            'maxEntries' => $maxEntries,
        ])
        ->createElementWithFields();
    $owner = $fixture->element;
    $field = Fields::getFieldById($fixture->field('matrixField')->id);
    $entries = [];
    $sortOrder = [];

    foreach ($titles as $title) {
        $uid = 'uid:'.Str::uuid();
        $entries[$uid] = ['type' => $nestedType->handle, 'title' => $title];
        $sortOrder[] = $uid;
    }

    $owner->setFieldValueFromRequest('matrixField', [
        'entries' => $entries,
        'sortOrder' => $sortOrder,
    ]);
    expect(app(Elements::class)->saveElement($owner))->toBeTrue();
    $owner = Entry::find()->id($owner->id)->one();
    $nestedEntries = $owner->getFieldValue('matrixField')->status(null)->all();
    $nested = $nestedEntries[0];
    $draft = app(Drafts::class)->createDraft($owner, auth()->id(), provisional: true);
    $control = $field->formControl(new FieldContext(path: 'matrixField', element: $draft));

    return compact('owner', 'field', 'nested', 'nestedEntries', 'draft', 'control');
}

it('requires authentication', function () {
    auth()->logout();

    postJson(action(PerformElementActionController::class), [
        'elementType' => Entry::class,
    ])->assertUnauthorized();
});

it('validates required request params', function () {
    ($this->performElementAction)([
        'elementType' => Entry::class,
    ])->assertUnprocessable();
});

it('returns 400 for unsupported actions', function () {
    ($this->performElementAction)([
        'elementType' => Entry::class,
        'elementAction' => Delete::class,
        'elementIds' => [1],
        'source' => null,
    ])->assertStatus(400);
});

it('returns native Laravel download responses for download actions', function () {
    $entry = EntryModel::factory()->createElement();

    $action = new class extends ElementAction
    {
        public static function isDownload(): bool
        {
            return true;
        }

        public function performAction(ElementQueryInterface $query): bool
        {
            $this->setResponse(new Response(
                content: 'downloaded',
                status: 200,
                headers: [
                    'Content-Disposition' => 'attachment; filename=entries.txt',
                ],
            ));

            return true;
        }
    };

    Event::listen(function (ElementActionsResolving $event) use ($action) {
        if ($event->elementType === Entry::class) {
            $event->actions[] = clone $action;
        }
    });

    $response = post(action(PerformElementActionController::class), [
        'context' => 'index',
        'source' => '*',
        'viewState' => [
            'mode' => 'table',
            'static' => false,
        ],
        'elementType' => Entry::class,
        'elementAction' => $action::class,
        'elementIds' => [$entry->id],
    ]);

    $response->assertOk();

    expect($response->headers->get('content-disposition'))->toContain('entries.txt')
        ->and($response->getContent())->toBe('downloaded');
});

it('passes a redirecting action response on as the JSON redirect', function () {
    $entry = EntryModel::factory()->createElement();

    $action = new class extends ElementAction
    {
        public function performAction(ElementQueryInterface $query): bool
        {
            $this->setResponse(new RedirectResponse('https://example.test/somewhere'));

            return true;
        }
    };

    Event::listen(function (ElementActionsResolving $event) use ($action) {
        if ($event->elementType === Entry::class) {
            $event->actions[] = clone $action;
        }
    });

    ($this->performElementAction)([
        'elementType' => Entry::class,
        'elementAction' => $action::class,
        'elementIds' => [$entry->id],
    ])
        ->assertOk()
        ->assertJsonPath('redirect', 'https://example.test/somewhere');
});

it('includes exporter metadata in the refreshed element response', function (string $context) {
    $entry = EntryModel::factory()->createElement();

    ($this->performElementAction)([
        'context' => $context,
        'source' => '__IMP__',
        'elementType' => Entry::class,
        'elementAction' => Delete::class,
        'elementIds' => [$entry->id],
    ])->assertOk()
        ->assertJsonStructure(['html'])
        ->assertJsonPath('exporters.0.type', Raw::class)
        ->assertJsonPath('exporters.0.formattable', true);

    expect(Entry::find()->id($entry->id)->status(null)->one())->toBeNull();
})->with([
    'standalone index' => ['index'],
    'legacy embedded index' => ['embedded-index'],
]);

it('applies an embedded action only to the derivative owner clone', function () {
    $fixture = embeddedActionFixture();
    $manager = $fixture['control']->props()['manager'];

    ($this->performElementAction)([
        ...$manager,
        'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
        'elementType' => Entry::class,
        'source' => '*',
        'baseCriteria' => ['ownerId' => 999999, 'fieldId' => 999999],
        'criteria' => ['trashed' => true],
        'elementAction' => SetStatus::class,
        'elementIds' => [$fixture['nested']->id],
        'status' => SetStatus::DISABLED,
    ])->assertOk()
        ->assertJsonStructure(['message'])
        ->assertJsonMissingPath('data')
        ->assertJsonMissingPath('pagination')
        ->assertJsonMissingPath('badgeCounts');

    $draftNested = Entry::find()
        ->fieldId($fixture['field']->id)
        ->ownerId($fixture['draft']->id)
        ->status(null)
        ->drafts(null)
        ->one();

    expect($draftNested->id)->not->toBe($fixture['nested']->id)
        ->and($draftNested->enabled)->toBeFalse()
        ->and(Entry::find()->id($fixture['nested']->id)->status(null)->one()->enabled)->toBeTrue();
});

it('downloads embedded selections without preparing owner-specific clones', function () {
    $fixture = embeddedActionFixture();
    $manager = $fixture['control']->props()['manager'];

    $action = new class extends ElementAction
    {
        public static function isDownload(): bool
        {
            return true;
        }

        public function performAction(ElementQueryInterface $query): bool
        {
            $this->setResponse(new Response(implode(',', $query->ids())));

            return true;
        }
    };

    Event::listen(function (ElementActionsResolving $event) use ($action) {
        if ($event->elementType === Entry::class) {
            $event->actions[] = clone $action;
        }
    });

    $response = post(action(PerformElementActionController::class), [
        ...$manager,
        'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
        'elementType' => Entry::class,
        'source' => '__IMP__',
        'elementAction' => $action::class,
        'elementIds' => [$fixture['nested']->id],
    ])->assertOk();

    expect($response->getContent())->toBe((string) $fixture['nested']->id)
        ->and(Entry::find()
            ->fieldId($fixture['field']->id)
            ->ownerId($fixture['draft']->id)
            ->status(null)
            ->drafts(null)
            ->ids())
        ->toBe([$fixture['nested']->id]);
});

it('rolls back prepared clones when an embedded action reports failure', function () {
    $fixture = embeddedActionFixture();
    $manager = $fixture['control']->props()['manager'];

    $action = new class extends ElementAction
    {
        public function performAction(ElementQueryInterface $query): bool
        {
            $this->setMessage('The embedded action failed.');

            return false;
        }
    };

    Event::listen(function (ElementActionsResolving $event) use ($action) {
        if ($event->elementType === Entry::class) {
            $event->actions[] = clone $action;
        }
    });

    ($this->performElementAction)([
        ...$manager,
        'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
        'elementType' => Entry::class,
        'source' => '__IMP__',
        'elementAction' => $action::class,
        'elementIds' => [$fixture['nested']->id],
    ])->assertBadRequest()
        ->assertJsonPath('message', 'The embedded action failed.');

    expect(Entry::find()
        ->fieldId($fixture['field']->id)
        ->ownerId($fixture['draft']->id)
        ->status(null)
        ->drafts(null)
        ->ids())
        ->toBe([$fixture['nested']->id]);
});

it('deletes only the prepared derivative nested entry', function () {
    $fixture = embeddedActionFixture();
    $manager = $fixture['control']->props()['manager'];

    ($this->performElementAction)([
        ...$manager,
        'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
        'elementType' => Entry::class,
        'source' => '__IMP__',
        'elementAction' => Delete::class,
        'elementIds' => [$fixture['nested']->id],
    ])->assertOk();

    $deletedDerivative = Entry::find()
        ->fieldId($fixture['field']->id)
        ->ownerId($fixture['draft']->id)
        ->status(null)
        ->drafts(null)
        ->trashed()
        ->one();

    expect(Entry::find()
        ->fieldId($fixture['field']->id)
        ->ownerId($fixture['draft']->id)
        ->status(null)
        ->ids())
        ->toBeEmpty()
        ->and($deletedDerivative?->id)->not->toBe($fixture['nested']->id)
        ->and($deletedDerivative?->getCanonicalId())->toBe($fixture['nested']->id)
        ->and(Entry::find()->id($fixture['nested']->id)->status(null)->one())
        ->not->toBeNull();
});

it('rolls back the prepared derivative when nested deletion fails', function () {
    $fixture = embeddedActionFixture();
    $manager = $fixture['control']->props()['manager'];

    Event::listen(ElementDeleting::class, function (ElementDeleting $event): void {
        $event->isValid = false;
    });

    ($this->performElementAction)([
        ...$manager,
        'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
        'elementType' => Entry::class,
        'source' => '__IMP__',
        'elementAction' => Delete::class,
        'elementIds' => [$fixture['nested']->id],
    ])->assertBadRequest()
        ->assertJsonPath('message', 'Could not delete 1 entry.');

    expect(Entry::find()
        ->fieldId($fixture['field']->id)
        ->ownerId($fixture['draft']->id)
        ->status(null)
        ->drafts(null)
        ->ids())
        ->toBe([$fixture['nested']->id])
        ->and(Entry::find()->id($fixture['nested']->id)->status(null)->one())
        ->not->toBeNull();
});

it('places each embedded duplicate immediately after its source in selection order', function () {
    $fixture = embeddedActionFixture(['Alpha', 'Bravo', 'Charlie']);
    $manager = $fixture['control']->props()['manager'];
    [$alpha, , $charlie] = $fixture['nestedEntries'];

    foreach ($fixture['nestedEntries'] as $index => $entry) {
        DB::table(Table::ELEMENTS_OWNERS)
            ->where('elementId', $entry->id)
            ->where('ownerId', $fixture['draft']->id)
            ->update(['sortOrder' => [10, 30, 70][$index]]);
    }

    ($this->performElementAction)([
        ...$manager,
        'sortable' => false,
        'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
        'elementType' => Entry::class,
        'source' => '__IMP__',
        'elementAction' => Duplicate::class,
        'elementIds' => [$charlie->id, $alpha->id],
    ])->assertOk();

    $titles = array_column(Entry::find()
        ->fieldId($fixture['field']->id)
        ->ownerId($fixture['draft']->id)
        ->status(null)
        ->drafts(null)
        ->orderBy('elements_owners.sortOrder')
        ->all(), 'title');

    expect($titles)->toBe(['Alpha', 'Alpha', 'Bravo', 'Charlie', 'Charlie']);
});

it('duplicates into a derivative owner without preparing the source', function () {
    $fixture = embeddedActionFixture(['Alpha', 'Bravo'], 3);
    $manager = $fixture['control']->props()['manager'];
    $source = $fixture['nestedEntries'][0];

    ($this->performElementAction)([
        ...$manager,
        'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
        'elementType' => Entry::class,
        'source' => '__IMP__',
        'elementAction' => Duplicate::class,
        'elementIds' => [$source->id],
    ])->assertOk();

    $ownedEntries = Entry::find()
        ->fieldId($fixture['field']->id)
        ->ownerId($fixture['draft']->id)
        ->status(null)
        ->drafts(null)
        ->orderBy('elements_owners.sortOrder')
        ->all();
    $copy = $ownedEntries[1];

    expect(array_column($ownedEntries, 'id'))->toContain($source->id)
        ->and($copy->id)->not->toBe($source->id)
        ->and($copy->getOwnerId())->toBe($fixture['draft']->id)
        ->and($copy->getPrimaryOwnerId())->toBe($fixture['draft']->id)
        ->and(collect($ownedEntries)
            ->contains(fn (Entry $entry): bool => $entry->id !== $source->id && $entry->getCanonicalId() === $source->id))
        ->toBeFalse()
        ->and(Entry::find()->id($source->id)->status(null)->one()?->getPrimaryOwnerId())
        ->toBe($fixture['owner']->id);
});

it('stops duplicating when the field maximum is reached', function () {
    $fixture = embeddedActionFixture(['Alpha', 'Bravo'], 3);
    $manager = $fixture['control']->props()['manager'];

    ($this->performElementAction)([
        ...$manager,
        'maxElements' => 100,
        'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
        'elementType' => Entry::class,
        'source' => '__IMP__',
        'elementAction' => Duplicate::class,
        'elementIds' => array_column($fixture['nestedEntries'], 'id'),
    ])->assertOk();

    $entries = Entry::find()
        ->fieldId($fixture['field']->id)
        ->ownerId($fixture['draft']->id)
        ->status(null)
        ->drafts(null)
        ->orderBy('elements_owners.sortOrder')
        ->all();

    expect(array_column($entries, 'title'))->toBe(['Alpha', 'Alpha', 'Bravo']);
});
