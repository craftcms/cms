<?php

declare(strict_types=1);

use craft\events\DefineBehaviorsEvent;
use craft\models\Site as LegacySite;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Yii2Adapter\Tests\TestCase;
use yii\base\Behavior;
use yii\base\Event;

uses(TestCase::class);

final class TestSiteBehavior extends Behavior
{
    private ?string $_foo = 'bar';

    public function attach($owner)
    {
        if (!$owner instanceof LegacySite) {
            throw new RuntimeException('TestSiteBehavior can only attach to legacy site instances.');
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
    Event::off(LegacySite::class, LegacySite::EVENT_DEFINE_BEHAVIORS);
});

test('legacy site extends the new site data class and exposes the define behaviors event constant', function() {
    $site = new LegacySite();

    expect($site)->toBeInstanceOf(Site::class)
        ->and(LegacySite::EVENT_DEFINE_BEHAVIORS)->toBe('defineBehaviors');
});

test('site data supports manual behavior attachment and delegated access via macros', function() {
    $site = new Site();

    $site->attachBehavior('test:site', TestSiteBehavior::class);

    expect($site->getBehavior('test:site'))->toBeInstanceOf(TestSiteBehavior::class)
        ->and($site->getBehavior(TestSiteBehavior::class))->toBeInstanceOf(TestSiteBehavior::class)
        ->and($site->getFoo())->toBe('bar')
        ->and($site->foo)->toBe('bar')
        ->and($site->getBehavior('test:site')?->owner)->toBeInstanceOf(LegacySite::class);

    $site->foo = 'baz';

    expect($site->getFoo())->toBe('baz');
});

test('site data lazily attaches behaviors defined via the legacy define behaviors event', function() {
    Event::on(LegacySite::class, LegacySite::EVENT_DEFINE_BEHAVIORS, function(DefineBehaviorsEvent $event) {
        $event->behaviors['event:site'] = TestSiteBehavior::class;
    });

    $site = new Site();

    expect($site->getBehavior('event:site'))->toBeInstanceOf(TestSiteBehavior::class)
        ->and($site->getFoo())->toBe('bar')
        ->and($site->foo)->toBe('bar');
});
