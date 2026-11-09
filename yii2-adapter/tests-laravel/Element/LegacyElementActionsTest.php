<?php

declare(strict_types=1);

use craft\base\ElementAction as LegacyElementAction;
use CraftCms\Cms\Asset\Actions\DeleteAssets;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Actions\Delete;
use CraftCms\Cms\Element\Actions\DeleteForSite;
use CraftCms\Cms\Element\Actions\Duplicate;
use CraftCms\Cms\Element\Actions\Restore;
use CraftCms\Cms\Element\Actions\SetStatus;
use CraftCms\Cms\Element\ElementActions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\User\Actions\SuspendUsers;
use CraftCms\Cms\User\Actions\UnsuspendUsers;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use CraftCms\Yii2Adapter\Element\LegacyElementActions;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;

uses(DatabaseTestCase::class);

beforeEach(function(): void {
    $this->actingAs(User::first());
    $this->elementActions = app(ElementActions::class);
});

function legacyActionConfig(string $type, string $elementType = Entry::class, array $config = []): array
{
    $actions = app(ElementActions::class);

    return $actions->serializeActions([
        $actions->createAction(['type' => $type, ...$config], $elementType),
    ])[0];
}

it('replaces the element actions service', function(): void {
    expect($this->elementActions)->toBeInstanceOf(LegacyElementActions::class);
});

it('gives built-in actions a trigger descriptor', function(): void {
    expect(legacyActionConfig(Delete::class, config: ['withDescendants' => true]))
        ->toMatchArray([
            'triggerId' => Delete::class . '-actiontrigger',
            'trigger' => null,
            'legacyTrigger' => [
                'handler' => 'delete',
                'withDescendants' => true,
                'hardDelete' => false,
                'confirmationMessage' => null,
            ],
        ])
        ->and(legacyActionConfig(DeleteAssets::class, Asset::class)['legacyTrigger']['handler'])->toBe('deleteAssets')
        ->and(legacyActionConfig(Duplicate::class, config: ['asDrafts' => true])['legacyTrigger'])
        ->toBe(['handler' => 'duplicate', 'attribute' => 'data-duplicatable-as-draft'])
        ->and(legacyActionConfig(Restore::class, config: ['restorableElementsOnly' => true])['legacyTrigger'])
        ->toBe(['handler' => 'restore', 'attribute' => 'data-restorable'])
        ->and(legacyActionConfig(SuspendUsers::class, UserElement::class)['legacyTrigger'])
        ->toBe(['handler' => 'suspendUsers', 'currentUserId' => User::first()->id]);
});

it('renders trigger markup for the actions that have it', function(): void {
    expect(legacyActionConfig(SetStatus::class)['trigger'])->toContain('data-param="status"')
        ->and(legacyActionConfig(Restore::class)['trigger'])->toBe('<div class="btn formsubmit">Restore</div>');
});

it('gives repeated built-in actions distinct trigger IDs', function(): void {
    $serialized = $this->elementActions->serializeActions([
        $this->elementActions->createAction(Delete::class, Entry::class),
        $this->elementActions->createAction(['type' => Delete::class, 'withDescendants' => true], Entry::class),
    ]);

    expect(array_column($serialized, 'triggerId'))->toBe([
        Delete::class . '-actiontrigger',
        Delete::class . '-actiontrigger-2',
    ]);
});

it('uses the trigger an action defines itself', function(): void {
    $action = new class() extends SetStatus {
        public function getTriggerId(): string
        {
            return 'custom-trigger';
        }

        public function getTriggerHtml(): ?string
        {
            return '<button class="btn">Custom</button>';
        }
    };
    $action->setElementType(Entry::class);

    expect($this->elementActions->serializeActions([$action])[0])
        ->toMatchArray(['triggerId' => 'custom-trigger', 'trigger' => '<button class="btn">Custom</button>'])
        ->not->toHaveKey('legacyTrigger');
});

it('lists legacy actions without their own trigger in the actions menu', function(): void {
    $action = new class() extends LegacyElementAction {
    };
    $action->setElementType(Entry::class);

    expect($this->elementActions->serializeActions([$action])[0])
        ->toMatchArray(['triggerId' => $action::class . '-actiontrigger', 'trigger' => null, 'legacyTrigger' => null]);
});

it('has a legacy index handler for every built-in trigger', function(): void {
    $handlers = collect([
        [Delete::class, Entry::class],
        [DeleteAssets::class, Asset::class],
        [Duplicate::class, Entry::class],
        [DeleteForSite::class, Entry::class],
        [Restore::class, Entry::class],
        [SetStatus::class, Entry::class],
        [SuspendUsers::class, UserElement::class],
        [UnsuspendUsers::class, UserElement::class],
    ])->map(fn(array $action) => legacyActionConfig(...$action)['legacyTrigger']['handler']);

    $script = file_get_contents(dirname(__DIR__, 3) . '/packages/craftcms-legacy/cpcompat/src/element-index/action-triggers.js');
    preg_match_all('/^  (\w+): \(/m', $script, $matches);

    expect($matches[1])->toEqualCanonicalizing($handlers->all());
});
