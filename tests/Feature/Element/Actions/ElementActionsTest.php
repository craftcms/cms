<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Element\Actions\ClientAction;
use CraftCms\Cms\Element\Actions\Delete;
use CraftCms\Cms\Element\Actions\Duplicate;
use CraftCms\Cms\Element\Actions\ElementAction;
use CraftCms\Cms\Element\Actions\Restore;
use CraftCms\Cms\Element\Actions\SetStatus;
use CraftCms\Cms\Element\ElementActions;
use CraftCms\Cms\Element\Events\ElementActionPerformed;
use CraftCms\Cms\Element\Events\ElementActionPerforming;
use CraftCms\Cms\Element\Events\ElementActionsResolving;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\User\Actions\SuspendUsers;
use CraftCms\Cms\User\Actions\UnsuspendUsers;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());

    $this->elementActions = app(ElementActions::class);
});

/**
 * Element actions by class, client actions by key.
 */
function elementActionIds(array $actions): array
{
    return array_map(fn ($action) => $action instanceof ClientAction ? $action->key : $action::class, $actions);
}

it('creates actions from strings, arrays, and existing instances', function () {
    $fromString = $this->elementActions->createAction(Delete::class, Entry::class);
    $fromArray = $this->elementActions->createAction([
        'type' => Delete::class,
        'successMessage' => 'Deleted from array.',
    ], Entry::class);
    $existing = new Delete;
    $fromInstance = $this->elementActions->createAction($existing, Entry::class);

    expect($fromString)->toBeInstanceOf(Delete::class)
        ->and($fromString->getConfirmationMessage())->toContain('entries')
        ->and($fromArray)->toBeInstanceOf(Delete::class)
        ->and($fromArray->successMessage)->toBe('Deleted from array.')
        ->and($fromInstance)->toBe($existing)
        ->and($fromInstance->getConfirmationMessage())->toContain('entries');
});

it('resolves canonical entry actions for non-trashed queries', function () {
    $types = elementActionIds($this->elementActions->availableActions(Entry::class, '*', Entry::find()));

    expect($types)->toContain('view')
        ->and($types)->toContain('edit')
        ->and($types)->toContain(Duplicate::class)
        ->and($types)->toContain(SetStatus::class)
        ->and($types)->toContain(Delete::class)
        ->and($types)->not->toContain(Restore::class);
});

it('flags non-bulk actions when serializing action items', function () {
    $actions = $this->elementActions->availableActions(Entry::class, '*', Entry::find());
    $items = $this->elementActions->serializeActionItems($actions);
    $flags = array_column($items, 'bulk', 'key');

    expect($flags['view'])->toBeFalse()
        ->and($flags['edit'])->toBeFalse()
        ->and($flags[Duplicate::class] ?? null)->toBeNull()
        ->and($flags[Delete::class] ?? null)->toBeNull();
});

it('serializes client-owned element actions as events', function () {
    $actions = $this->elementActions->availableActions(Entry::class, '*', Entry::find());
    $items = collect($this->elementActions->serializeActionItems($actions))->keyBy('key');

    expect($items['copy']['action'])->toBe([
        'type' => 'event',
        'name' => 'craft:copy-elements',
    ])->and($items['edit']['action'])->toBe([
        'type' => 'event',
        'name' => 'craft:edit-element',
    ])->and($items['view']['action'])->toBe([
        'type' => 'event',
        'name' => 'craft:view-element',
    ]);

    $duplicate = $items[Duplicate::class];

    expect($duplicate['action']['type'])->toBe('http')
        ->and($duplicate['action']['url'])->toContain('element-indexes/perform-action');
});

it('serializes a client action with its detail and the capability it needs', function () {
    $items = $this->elementActions->serializeActionItems([
        new ClientAction(
            key: 'plugin:archive',
            label: 'Archive',
            event: 'plugin:archive',
            detail: ['folderId' => 3],
            bulk: false,
            destructive: true,
            selectionAttribute: 'archivable',
        ),
    ]);

    expect($items)->toBe([[
        'key' => 'plugin:archive',
        'label' => 'Archive',
        'destructive' => true,
        'variant' => 'danger',
        'bulk' => false,
        'selectionAttribute' => 'archivable',
        'action' => [
            'type' => 'event',
            'name' => 'plugin:archive',
            'detail' => ['folderId' => 3],
        ],
    ]]);
});

it('serializes standard selection capabilities for page bulk actions', function () {
    $actions = $this->elementActions->availableActions(Entry::class, '*', Entry::find());
    $items = collect($this->elementActions->serializeActionItems($actions))->keyBy('key');

    expect($items['copy']['selectionAttribute'])->toBe('copyable')
        ->and($items[Duplicate::class]['selectionAttribute'])->toBe('duplicatable')
        ->and($items[Delete::class]['selectionAttribute'])->toBe('deletable')
        ->and($items['compare']['selectionAttribute'])->toBe('comparable')
        ->and($items['compare']['selectionCount'])->toBe(2)
        ->and($items['compare']['action'])->toBe(['type' => 'event', 'name' => 'craft:compare-elements']);
});

it('serializes registered download actions for native form submission', function () {
    $downloadAction = new class extends ElementAction
    {
        public static function isDownload(): bool
        {
            return true;
        }

        public function getTriggerLabel(): string
        {
            return 'Download entries';
        }
    };

    Event::listen(function (ElementActionsResolving $event) use ($downloadAction) {
        if ($event->elementType === Entry::class) {
            $event->actions[] = clone $downloadAction;
        }
    });

    $actions = $this->elementActions->availableActions(Entry::class, '*', Entry::find());
    $items = collect($this->elementActions->serializeActionItems($actions));
    $download = $items->firstWhere('key', $downloadAction::class);

    expect($download)->not->toBeNull()
        ->and($download['label'])->toBe('Download entries')
        ->and($download['action']['type'])->toBe('download')
        ->and($download['action']['method'])->toBe('POST')
        ->and($download['action']['url'])->toContain('element-indexes/perform-action')
        ->and($download['action']['body']['elementAction'])->toBe($downloadAction::class);
});

it('puts restore first for trashed queries', function () {
    $actions = $this->elementActions->availableActions(Entry::class, '*', Entry::find()->trashed());

    expect($actions[0]::class)->toBe(Restore::class);
});

it('resolves canonical user and address actions', function () {
    $userActions = elementActionIds($this->elementActions->availableActions(User::class, '*', User::find()->status(null)));
    $addressActions = elementActionIds($this->elementActions->availableActions(Address::class, '*', Address::find()));

    expect($userActions)->toContain(SuspendUsers::class)
        ->and($userActions)->toContain(UnsuspendUsers::class)
        ->and($addressActions)->toContain('copy');
});

it('leaves client actions out of the legacy index action configs', function () {
    $serialized = $this->elementActions->serializeActions([
        ClientAction::copy(),
        $this->elementActions->createAction(Delete::class, Entry::class),
    ]);

    expect(array_column($serialized, 'type'))->toBe([Delete::class]);
});

it('never resolves a client action to perform', function () {
    $actions = $this->elementActions->availableActions(Entry::class, '*', Entry::find());

    expect($this->elementActions->resolveAction($actions, ClientAction::class))->toBeNull();
});

it('resolves a cloned matching action and returns null when missing', function () {
    $actions = [
        $this->elementActions->createAction(Delete::class, Entry::class),
        $this->elementActions->createAction(Duplicate::class, Entry::class),
    ];

    $resolved = $this->elementActions->resolveAction($actions, Delete::class);
    $missing = $this->elementActions->resolveAction($actions, Restore::class);

    expect($resolved)->toBeInstanceOf(Delete::class)
        ->and($resolved)->not->toBe($actions[0])
        ->and($missing)->toBeNull();
});

it('invokes actions and dispatches before and after events on success', function () {
    Event::fake([
        ElementActionPerforming::class,
        ElementActionPerformed::class,
    ]);

    $action = new class extends ElementAction
    {
        public function performAction(ElementQueryInterface $query): bool
        {
            $this->setMessage('Action succeeded.');

            return true;
        }
    };

    $result = $this->elementActions->invoke($action, Entry::find()->id([]));

    expect($result)->toBe([
        'valid' => true,
        'success' => true,
        'message' => 'Action succeeded.',
    ]);

    Event::assertDispatched(ElementActionPerforming::class);
    Event::assertDispatched(ElementActionPerformed::class);
});

it('returns an invalid result when action validation fails', function () {
    $action = new class extends ElementAction
    {
        public ?string $status = null;

        public function getRules(): array
        {
            return [
                'status' => ['required'],
            ];
        }
    };

    $result = $this->elementActions->invoke($action, Entry::find()->id([]));

    expect($result)->toBe([
        'valid' => false,
        'success' => false,
        'message' => null,
    ]);
});
