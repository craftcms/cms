<?php

declare(strict_types=1);

use CraftCms\Cms\User\Conditions\AdminConditionRule;
use CraftCms\Cms\User\Conditions\UserCondition;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;

it('matchElement returns expected result', function (Closure $createUser, bool $ruleValue, bool $expected) {
    $condition = new UserCondition(User::class);
    $rule = $condition->createConditionRule(AdminConditionRule::class);
    $rule->value = $ruleValue;

    $user = $createUser();

    expect($rule->matchElement($user))->toBe($expected);
})->with([
    'true for admin when value is true' => [
        fn () => User::find()->admin(true)->one(),
        true,
        true,
    ],
    'false for non-admin when value is true' => [
        function () {
            $nonAdmin = UserModel::factory()->create(['admin' => false]);

            return User::find()->id($nonAdmin->id)->one();
        },
        true,
        false,
    ],
    'true for non-admin when value is false' => [
        function () {
            $nonAdmin = UserModel::factory()->create(['admin' => false]);

            return User::find()->id($nonAdmin->id)->one();
        },
        false,
        true,
    ],
]);

it('modifyQuery filters by admin value', function (bool $ruleValue, bool $expectedAdmin) {
    UserModel::factory()->create(['admin' => false]);

    $condition = new UserCondition(User::class);
    $rule = $condition->createConditionRule(AdminConditionRule::class);
    $rule->value = $ruleValue;

    $query = User::find();
    $rule->modifyQuery($query);

    $results = $query->all();

    expect($results)->not->toBeEmpty();
    expect(collect($results)->every(fn (User $user) => $user->admin === $expectedAdmin))->toBeTrue();
})->with([
    'filters to admins only' => [true, true],
    'filters to non-admins only' => [false, false],
]);

test('getConfig includes value key', function () {
    $condition = new UserCondition(User::class);
    $rule = $condition->createConditionRule(AdminConditionRule::class);
    $rule->value = true;

    $config = $rule->getConfig();

    expect($config)->toHaveKey('value', true)
        ->toHaveKey('class', AdminConditionRule::class)
        ->toHaveKey('uid');
});

test('config round-trip preserves behavior', function () {
    $condition = new UserCondition(User::class);
    $rule = $condition->createConditionRule(AdminConditionRule::class);
    $rule->value = false;

    $config = $rule->getConfig();

    // Restore rule from its config (remove 'class' as it's metadata, not a property)
    unset($config['class']);
    $restoredRule = new AdminConditionRule($config);

    expect($restoredRule)->toBeInstanceOf(AdminConditionRule::class);
    expect($restoredRule->value)->toBeFalse();
    expect($restoredRule->uid)->toBe($rule->uid);

    // Verify the restored rule has the same matching behavior
    $nonAdmin = UserModel::factory()->create(['admin' => false]);
    $nonAdminUser = User::find()->id($nonAdmin->id)->one();

    expect($restoredRule->matchElement($nonAdminUser))->toBeTrue();
});
