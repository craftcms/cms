<?php

declare(strict_types=1);

use craft\auth\methods\TOTP as LegacyTOTP;
use craft\base\Component as LegacyComponent;
use craft\base\Model as LegacyModel;
use craft\behaviors\FieldLayoutBehavior;
use craft\config\GeneralConfig as LegacyGeneralConfig;
use craft\elements\GlobalSet as LegacyGlobalSet;
use craft\events\DefineBehaviorsEvent;
use craft\fields\PlainText as LegacyPlainText;
use craft\models\DeprecationError as LegacyDeprecationError;
use craft\models\GqlSchema as LegacyGqlSchema;
use craft\models\GqlToken as LegacyGqlToken;
use craft\models\UserGroup as LegacyUserGroup;
use CraftCms\Cms\Deprecator\Models\DeprecationError;
use CraftCms\Cms\Field\Data\JsonData;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Data\GqlToken;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Yii2Adapter\Behavior\LegacyBehaviorCatalog;
use CraftCms\Yii2Adapter\Tests\TestCase;
use Illuminate\Support\Collection;
use yii\base\Behavior;
use yii\base\Event;

uses(TestCase::class);

class TestCompatibilityBehavior extends Behavior
{
    public string $value = 'bar';

    public function getFoo(): string
    {
        return $this->value;
    }

    public function setFoo(string $value): void
    {
        $this->value = $value;
    }

    public function updateOwnerDescription(string $description): void
    {
        $this->owner->description = $description;
    }
}

function sandboxAllowedCompatibilityTargets(): Collection
{
    return collect(LegacyBehaviorCatalog::discoveredTargets())
        ->pluck('targetClass')
        ->unique()
        ->filter(fn(string $class) => (new ReflectionClass($class))->getAttributes(AllowedInSandbox::class) !== [])
        ->values();
}

afterEach(function() {
    foreach ([
        LegacyModel::class,
        LegacyComponent::class,
        LegacyUserGroup::class,
        LegacyGeneralConfig::class,
        LegacyPlainText::class,
        LegacyDeprecationError::class,
        LegacyTOTP::class,
    ] as $class) {
        Event::off($class, 'defineBehaviors');
    }
});

test('legacy behavior catalog discovers migrated behavior targets across the main buckets', function() {
    $targets = collect(LegacyBehaviorCatalog::discoveredTargets())->keyBy('legacyClass');

    expect($targets)
        ->toHaveKey(LegacyGqlSchema::class)
        ->toHaveKey(LegacyGqlToken::class)
        ->toHaveKey(LegacyUserGroup::class)
        ->toHaveKey(LegacyPlainText::class)
        ->toHaveKey(LegacyDeprecationError::class)
        ->and($targets[LegacyGqlSchema::class]['targetClass'])->toBe(GqlSchema::class)
        ->and($targets[LegacyGqlToken::class]['targetClass'])->toBe(GqlToken::class)
        ->and($targets[LegacyUserGroup::class]['targetClass'])->toBe(UserGroup::class)
        ->and($targets[LegacyPlainText::class]['targetClass'])->toBe(PlainText::class)
        ->and($targets[LegacyDeprecationError::class]['targetClass'])->toBe(DeprecationError::class);
});

test('legacy behavior mixins are applied to every discovered compatibility target', function() {
    $targetClasses = collect(LegacyBehaviorCatalog::discoveredTargets())
        ->pluck('targetClass')
        ->unique()
        ->diff(sandboxAllowedCompatibilityTargets())
        ->values()
        ->all();

    expect(array_values(array_diff($targetClasses, LegacyBehaviorCatalog::mixinTargets())))
        ->toBe([]);
});

test('sandbox allowed classes are not exposed to legacy behavior mixins', function() {
    expect(collect(LegacyBehaviorCatalog::discoveredTargets())->pluck('targetClass'))
        ->toContain(JsonData::class)
        ->and(sandboxAllowedCompatibilityTargets())->toContain(JsonData::class)
        ->and(array_intersect(sandboxAllowedCompatibilityTargets()->all(), LegacyBehaviorCatalog::mixinTargets()))->toBe([]);
});

test('discovered behavior targets resolve through their legacy aliases to their migrated classes', function() {
    $aliasTargets = collect(LegacyBehaviorCatalog::discoveredTargets())
        ->filter(fn(array $target) => (new ReflectionClass($target['legacyClass']))->getName() !== $target['legacyClass'])
        ->values();

    expect($aliasTargets)->not->toBeEmpty();

    $aliasTargets->each(function(array $target) {
        expect((new ReflectionClass($target['legacyClass']))->getName())
            ->toBe($target['targetClass']);
    });
});

test('component-backed classes inherit behaviors from base model, base component, and concrete legacy classes', function() {
    Event::on(LegacyModel::class, LegacyModel::EVENT_DEFINE_BEHAVIORS, function(DefineBehaviorsEvent $event) {
        $event->behaviors['model'] = [
            'class' => TestCompatibilityBehavior::class,
            'value' => 'model',
        ];
    });

    Event::on(LegacyComponent::class, LegacyComponent::EVENT_DEFINE_BEHAVIORS, function(DefineBehaviorsEvent $event) {
        $event->behaviors['component'] = [
            'class' => TestCompatibilityBehavior::class,
            'value' => 'component',
        ];
    });

    Event::on(LegacyUserGroup::class, LegacyUserGroup::EVENT_DEFINE_BEHAVIORS, function(DefineBehaviorsEvent $event) {
        $event->behaviors['concrete'] = [
            'class' => TestCompatibilityBehavior::class,
            'value' => 'concrete',
        ];
    });

    $group = new UserGroup();

    expect($group->getBehavior('model'))->toBeInstanceOf(TestCompatibilityBehavior::class)
        ->and($group->getBehavior('component'))->toBeInstanceOf(TestCompatibilityBehavior::class)
        ->and($group->getBehavior('concrete'))->toBeInstanceOf(TestCompatibilityBehavior::class)
        ->and($group->getBehavior('concrete')?->owner)->toBeInstanceOf(LegacyUserGroup::class)
        ->and($group->getBehavior('model')?->getFoo())->toBe('model')
        ->and($group->getBehavior('component')?->getFoo())->toBe('component')
        ->and($group->getBehavior('concrete')?->getFoo())->toBe('concrete');

    $group->updateOwnerDescription('synced');

    expect($group->description)->toBe('synced');
});

test('legacy element classes expose class-defined behaviors', function() {
    $globalSet = new LegacyGlobalSet();

    expect($globalSet->getBehavior('fieldLayout'))->toBeInstanceOf(FieldLayoutBehavior::class);
});

test('field aliases inherit the define behaviors constant and support delegated method and property access', function() {
    $field = new PlainText();

    $field->attachBehavior('field:test', [
        'class' => TestCompatibilityBehavior::class,
        'value' => 'field',
    ]);

    expect(LegacyPlainText::EVENT_DEFINE_BEHAVIORS)->toBe('defineBehaviors')
        ->and($field->getBehavior('field:test'))->toBeInstanceOf(TestCompatibilityBehavior::class)
        ->and($field->getFoo())->toBe('field')
        ->and($field->foo)->toBe('field');

    $field->foo = 'updated';

    expect($field->getFoo())->toBe('updated');
});

test('legacy auth and config wrappers expose define behaviors on the legacy class', function() {
    expect(LegacyTOTP::EVENT_DEFINE_BEHAVIORS)->toBe('defineBehaviors')
        ->and(LegacyGeneralConfig::EVENT_DEFINE_BEHAVIORS)->toBe('defineBehaviors');
});
