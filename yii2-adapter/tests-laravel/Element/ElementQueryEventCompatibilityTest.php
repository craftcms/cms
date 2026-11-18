<?php

declare(strict_types=1);

use craft\base\Event as YiiEvent;
use craft\elements\db\ElementQuery as LegacyElementQuery;
use craft\elements\db\UserQuery as LegacyUserQuery;
use craft\events\CancelableEvent;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;

uses(DatabaseTestCase::class);

afterEach(function(): void {
    YiiEvent::off(LegacyUserQuery::class, LegacyElementQuery::EVENT_BEFORE_PREPARE);
    YiiEvent::off(LegacyElementQuery::class, LegacyElementQuery::EVENT_AFTER_PREPARE);
});

it('lets legacy prepare handlers scope and cancel element queries', function(): void {
    [$a, $b, $c] = UserModel::factory()->count(3)->create()->pluck('id')->all();
    $cancel = false;

    YiiEvent::on(LegacyUserQuery::class, LegacyElementQuery::EVENT_BEFORE_PREPARE, function(CancelableEvent $event) use ($a, $b, &$cancel): void {
        $event->sender->id([$a, $b]);
        $event->isValid = !$cancel;
    });
    YiiEvent::on(LegacyElementQuery::class, LegacyElementQuery::EVENT_AFTER_PREPARE, function(CancelableEvent $event) use ($a, $c): void {
        $event->sender->andWhere(['elements.id' => [$a, $c]]);
    });

    expect(User::find()->status(null)->ids())->toBe([$a])
        ->and(User::find()->status(null)->count())->toBe(1);

    $cancel = true;

    expect(User::find()->status(null)->ids())->toBe([])
        ->and(User::find()->status(null)->count())->toBe(0);
});
