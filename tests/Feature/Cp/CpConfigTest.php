<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Cp;
use CraftCms\Cms\Support\Composer;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;

use function Pest\Laravel\actingAs;

it('does not probe Composer for users who cannot perform package updates', function () {
    UserModel::whereKey(1)->update(['admin' => false]);
    actingAs(User::find()->id(1)->one());
    $this->mock(Composer::class)->shouldNotReceive('getIsAvailableForUi');

    expect(Cp::config()->get('allowUpdates'))->toBeFalse();
});

it('reports the password timeout as the elevated session duration', function (int $passwordTimeout, int $expected) {
    actingAs(User::findOne());
    config()->set('auth.password_timeout', $passwordTimeout);

    expect(Cp::config()->get('elevatedSessionDuration'))->toBe($expected);
})->with([
    'enabled' => [42, 42],
    'disabled' => [-1, 0],
]);
