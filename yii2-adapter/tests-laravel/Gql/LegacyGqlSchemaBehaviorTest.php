<?php

declare(strict_types=1);

use craft\events\DefineBehaviorsEvent;
use craft\models\GqlSchema as LegacyGqlSchema;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Yii2Adapter\Tests\TestCase;
use yii\base\Behavior;
use yii\base\Event;

uses(TestCase::class);

class TestGqlSchemaBehavior extends Behavior
{
    private ?string $_foo = 'bar';

    public function attach($owner)
    {
        if (!$owner instanceof LegacyGqlSchema) {
            throw new RuntimeException('TestGqlSchemaBehavior can only attach to legacy gql schema instances.');
        }

        parent::attach($owner);
    }

    public function getFoo(): ?string
    {
        return $this->_foo;
    }

    public function setFoo(?string $foo): void
    {
        $this->_foo = $foo;
    }
}

afterEach(function() {
    Event::off(LegacyGqlSchema::class, LegacyGqlSchema::EVENT_DEFINE_BEHAVIORS);
});

test('legacy gql schema extends the new data class and exposes the define behaviors event constant', function() {
    $schema = new LegacyGqlSchema();

    expect($schema)->toBeInstanceOf(GqlSchema::class)
        ->and(LegacyGqlSchema::EVENT_DEFINE_BEHAVIORS)->toBe('defineBehaviors');
});

test('gql schema data supports manual behavior attachment and delegated access via macros', function() {
    $schema = new GqlSchema();

    $schema->attachBehavior('test:gql-schema', TestGqlSchemaBehavior::class);

    expect($schema->getBehavior('test:gql-schema'))->toBeInstanceOf(TestGqlSchemaBehavior::class)
        ->and($schema->getBehavior(TestGqlSchemaBehavior::class))->toBeInstanceOf(TestGqlSchemaBehavior::class)
        ->and($schema->getFoo())->toBe('bar')
        ->and($schema->foo)->toBe('bar')
        ->and($schema->getBehavior('test:gql-schema')?->owner)->toBeInstanceOf(LegacyGqlSchema::class);

    $schema->foo = 'baz';

    expect($schema->getFoo())->toBe('baz');
});

test('gql schema data lazily attaches behaviors defined via the legacy define behaviors event', function() {
    Event::on(LegacyGqlSchema::class, LegacyGqlSchema::EVENT_DEFINE_BEHAVIORS, function(DefineBehaviorsEvent $event) {
        $event->behaviors['event:gql-schema'] = TestGqlSchemaBehavior::class;
    });

    $schema = new GqlSchema();

    expect($schema->getBehavior('event:gql-schema'))->toBeInstanceOf(TestGqlSchemaBehavior::class)
        ->and($schema->getFoo())->toBe('bar')
        ->and($schema->foo)->toBe('bar');
});
