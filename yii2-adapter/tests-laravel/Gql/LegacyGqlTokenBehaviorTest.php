<?php

declare(strict_types=1);

use craft\events\DefineBehaviorsEvent;
use craft\models\GqlToken as LegacyGqlToken;
use CraftCms\Cms\Gql\Data\GqlToken;
use CraftCms\Yii2Adapter\Tests\TestCase;
use yii\base\Behavior;
use yii\base\Event;

uses(TestCase::class);

class TestGqlTokenBehavior extends Behavior
{
    private ?string $_foo = 'bar';

    public function attach($owner)
    {
        if (!$owner instanceof LegacyGqlToken) {
            throw new RuntimeException('TestGqlTokenBehavior can only attach to legacy gql token instances.');
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
    Event::off(LegacyGqlToken::class, LegacyGqlToken::EVENT_DEFINE_BEHAVIORS);
});

test('legacy gql token extends the new data class and exposes the define behaviors event constant', function() {
    $token = new LegacyGqlToken();

    expect($token)->toBeInstanceOf(GqlToken::class)
        ->and(LegacyGqlToken::EVENT_DEFINE_BEHAVIORS)->toBe('defineBehaviors');
});

test('gql token data supports manual behavior attachment and delegated access via macros', function() {
    $token = new GqlToken();

    $token->attachBehavior('test:gql-token', TestGqlTokenBehavior::class);

    expect($token->getBehavior('test:gql-token'))->toBeInstanceOf(TestGqlTokenBehavior::class)
        ->and($token->getBehavior(TestGqlTokenBehavior::class))->toBeInstanceOf(TestGqlTokenBehavior::class)
        ->and($token->getFoo())->toBe('bar')
        ->and($token->foo)->toBe('bar')
        ->and($token->getBehavior('test:gql-token')?->owner)->toBeInstanceOf(LegacyGqlToken::class);

    $token->foo = 'baz';

    expect($token->getFoo())->toBe('baz');
});

test('gql token data lazily attaches behaviors defined via the legacy define behaviors event', function() {
    Event::on(LegacyGqlToken::class, LegacyGqlToken::EVENT_DEFINE_BEHAVIORS, function(DefineBehaviorsEvent $event) {
        $event->behaviors['event:gql-token'] = TestGqlTokenBehavior::class;
    });

    $token = new GqlToken();

    expect($token->getBehavior('event:gql-token'))->toBeInstanceOf(TestGqlTokenBehavior::class)
        ->and($token->getFoo())->toBe('bar')
        ->and($token->foo)->toBe('bar');
});
