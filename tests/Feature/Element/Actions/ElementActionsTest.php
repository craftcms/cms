<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Element\Actions\ChangeSortOrder;
use CraftCms\Cms\Element\Actions\Copy;
use CraftCms\Cms\Element\Actions\Delete;
use CraftCms\Cms\Element\Actions\Duplicate;
use CraftCms\Cms\Element\Actions\Edit;
use CraftCms\Cms\Element\Actions\ElementAction;
use CraftCms\Cms\Element\Actions\MoveDown;
use CraftCms\Cms\Element\Actions\MoveUp;
use CraftCms\Cms\Element\Actions\Restore;
use CraftCms\Cms\Element\Actions\SetStatus;
use CraftCms\Cms\Element\Actions\View;
use CraftCms\Cms\Element\ElementActions;
use CraftCms\Cms\Element\Events\ElementActionPerformed;
use CraftCms\Cms\Element\Events\ElementActionPerforming;
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
    $actions = $this->elementActions->availableActions(Entry::class, '*', Entry::find());
    $types = array_map(fn ($action) => $action::class, $actions);

    expect($types)->toContain(View::class)
        ->and($types)->toContain(Edit::class)
        ->and($types)->toContain(Duplicate::class)
        ->and($types)->toContain(SetStatus::class)
        ->and($types)->toContain(Delete::class)
        ->and($types)->not->toContain(Restore::class);
});

it('flags non-bulk actions when serializing action items', function () {
    $actions = $this->elementActions->availableActions(Entry::class, '*', Entry::find());
    $items = $this->elementActions->serializeActionItems($actions);
    $flags = array_column($items, 'bulk', 'key');

    expect($flags[View::class])->toBeFalse()
        ->and($flags[Edit::class])->toBeFalse()
        ->and($flags[Duplicate::class] ?? null)->toBeNull()
        ->and($flags[Delete::class] ?? null)->toBeNull();
});

it('serializes Copy as a client-side event action', function () {
    $actions = $this->elementActions->availableActions(Entry::class, '*', Entry::find());
    $items = collect($this->elementActions->serializeActionItems($actions));

    $copy = $items->firstWhere('key', Copy::class);

    expect($copy)->not->toBeNull()
        ->and($copy['action']['type'])->toBe('event')
        ->and($copy['action']['name'])->toBe('craft:copy-elements')
        ->and($copy['action'])->not->toHaveKey('url');

    // Duplicate stays a normal perform-endpoint POST.
    $duplicate = $items->firstWhere('key', Duplicate::class);

    expect($duplicate['action']['type'])->toBe('http')
        ->and($duplicate['action']['url'])->toContain('element-indexes/perform-action');
});

it('puts restore first for trashed queries', function () {
    $actions = $this->elementActions->availableActions(Entry::class, '*', Entry::find()->trashed());

    expect($actions[0]::class)->toBe(Restore::class);
});

it('resolves canonical user and address actions', function () {
    $userActions = array_map(
        fn ($action) => $action::class,
        $this->elementActions->availableActions(User::class, '*', User::find()->status(null)),
    );

    $addressActions = array_map(
        fn ($action) => $action::class,
        $this->elementActions->availableActions(Address::class, '*', Address::find()),
    );

    expect($userActions)->toContain(SuspendUsers::class)
        ->and($userActions)->toContain(UnsuspendUsers::class)
        ->and($addressActions)->toContain(Copy::class);
});

it('serializes nested-manager action configs with canonical types', function () {
    $owner = User::findOne();

    $serialized = $this->elementActions->serializeActions([
        new ChangeSortOrder($owner, 'addresses'),
        new MoveUp($owner, 'addresses'),
        new MoveDown($owner, 'addresses'),
    ]);

    expect(array_column($serialized, 'type'))->toBe([
        ChangeSortOrder::class,
        MoveUp::class,
        MoveDown::class,
    ]);
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
