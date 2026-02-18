<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\User\Conditions\AdminConditionRule;
use CraftCms\Cms\User\Conditions\CredentialedConditionRule;
use CraftCms\Cms\User\Conditions\EmailConditionRule;
use CraftCms\Cms\User\Conditions\FirstNameConditionRule;
use CraftCms\Cms\User\Conditions\GroupConditionRule;
use CraftCms\Cms\User\Conditions\LastNameConditionRule;
use CraftCms\Cms\User\Conditions\UserCondition;
use CraftCms\Cms\User\Conditions\UsernameConditionRule;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Models\UserGroup as UserGroupModel;
use Illuminate\Support\Facades\DB;

describe('AdminConditionRule', function () {
    it('matchElement returns true for admin user when value is true', function () {
        $condition = new UserCondition(User::class);
        $rule = $condition->createConditionRule(AdminConditionRule::class);
        $rule->value = true;

        $admin = User::find()->admin(true)->one();

        expect($rule->matchElement($admin))->toBeTrue();
    });

    it('modifyQuery filters by admin status', function (bool $ruleValue, bool $expectedAdmin) {
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
});

it('matchElement with text-based rules', function (string $ruleClass, string $factoryAttribute, string $factoryValue, string $ruleValue, bool $expected) {
    $userModel = UserModel::factory()->create([$factoryAttribute => $factoryValue]);

    $condition = new UserCondition(User::class);
    $rule = $condition->createConditionRule($ruleClass);
    $rule->operator = '=';
    $rule->value = $ruleValue;

    $element = User::find()->id($userModel->id)->one();

    expect($rule->matchElement($element))->toBe($expected);
})->with([
    'email matches' => [EmailConditionRule::class, 'email', 'test@example.com', 'test@example.com', true],
    'email does not match' => [EmailConditionRule::class, 'email', 'other@example.com', 'test@example.com', false],
    'username matches' => [UsernameConditionRule::class, 'username', 'craftuser', 'craftuser', true],
    'username does not match' => [UsernameConditionRule::class, 'username', 'otheruser', 'craftuser', false],
    'first name matches' => [FirstNameConditionRule::class, 'firstName', 'Alice', 'Alice', true],
    'first name does not match' => [FirstNameConditionRule::class, 'firstName', 'Bob', 'Alice', false],
    'last name matches' => [LastNameConditionRule::class, 'lastName', 'Smith', 'Smith', true],
    'last name does not match' => [LastNameConditionRule::class, 'lastName', 'Jones', 'Smith', false],
]);

it('modifyQuery filters users by text-based rules', function (string $ruleClass, string $factoryAttribute, string $operator, string $ruleValue, string $matchFactoryValue, string $otherFactoryValue, string $expectedFieldValue) {
    UserModel::factory()->create([$factoryAttribute => $matchFactoryValue]);
    UserModel::factory()->create([$factoryAttribute => $otherFactoryValue]);

    $condition = new UserCondition(User::class);
    $rule = $condition->createConditionRule($ruleClass);
    $rule->operator = $operator;
    $rule->value = $ruleValue;

    $query = User::find();
    $rule->modifyQuery($query);

    $results = $query->all();

    expect($results)->toHaveCount(1);
    expect($results[0]->$factoryAttribute)->toBe($expectedFieldValue);
})->with([
    'email exact match' => [EmailConditionRule::class, 'email', '=', 'findme@example.com', 'findme@example.com', 'notme@example.com', 'findme@example.com'],
    'username exact match' => [UsernameConditionRule::class, 'username', '=', 'targetuser', 'targetuser', 'anotheruser', 'targetuser'],
    'first name exact match' => [FirstNameConditionRule::class, 'firstName', '=', 'Charlie', 'Charlie', 'Diana', 'Charlie'],
    'last name begins with' => [LastNameConditionRule::class, 'lastName', 'bw', 'Will', 'Williams', 'Brown', 'Williams'],
]);

it('modifyQuery with contains operator finds partial matches', function () {
    UserModel::factory()->create(['email' => 'alice@craft.test']);
    UserModel::factory()->create(['email' => 'bob@other.test']);

    $condition = new UserCondition(User::class);
    $rule = $condition->createConditionRule(EmailConditionRule::class);
    $rule->operator = '**';
    $rule->value = 'craft.test';

    $query = User::find();
    $rule->modifyQuery($query);

    $results = $query->all();

    expect($results)->toHaveCount(1);
    expect($results[0]->email)->toBe('alice@craft.test');
});

describe('GroupConditionRule', function () {
    beforeEach(function () {
        DB::table(Table::USERGROUPS)->delete();
        Edition::set(Edition::Pro);
    });

    it('isSelectable returns true when groups exist', function () {
        UserGroupModel::factory()->create();

        expect(GroupConditionRule::isSelectable())->toBeTrue();
    });

    it('isSelectable returns false when no groups exist', function () {
        expect(GroupConditionRule::isSelectable())->toBeFalse();
    });

    it('matchElement returns true when user is in the specified group', function () {
        $group = UserGroupModel::factory()->create();
        $userModel = UserModel::factory()->create();
        $group->users()->attach($userModel);

        $condition = new UserCondition(User::class);
        $rule = $condition->createConditionRule(GroupConditionRule::class);
        $rule->operator = 'in';
        $rule->values = [$group->uid];

        $element = User::find()->id($userModel->id)->one();

        expect($rule->matchElement($element))->toBeTrue();
    });

    it('matchElement returns false when user is not in the specified group', function () {
        $group = UserGroupModel::factory()->create();
        $userModel = UserModel::factory()->create();

        $condition = new UserCondition(User::class);
        $rule = $condition->createConditionRule(GroupConditionRule::class);
        $rule->operator = 'in';
        $rule->values = [$group->uid];

        $element = User::find()->id($userModel->id)->one();

        expect($rule->matchElement($element))->toBeFalse();
    });

    it('modifyQuery filters users by group', function () {
        $group = UserGroupModel::factory()->create();
        $inGroup = UserModel::factory()->create();
        $notInGroup = UserModel::factory()->create();
        $group->users()->attach($inGroup);

        $condition = new UserCondition(User::class);
        $rule = $condition->createConditionRule(GroupConditionRule::class);
        $rule->operator = 'in';
        $rule->values = [$group->uid];

        $query = User::find();
        $rule->modifyQuery($query);

        $results = $query->all();

        expect(collect($results)->pluck('id')->toArray())->toContain($inGroup->id);
        expect(collect($results)->pluck('id')->toArray())->not->toContain($notInGroup->id);
    });
});

describe('CredentialedConditionRule', function () {
    it('matchElement with credentialed rule', function (array $userAttributes, bool $ruleValue, bool $clearStatus, bool $expected) {
        $userModel = UserModel::factory()->create($userAttributes);

        $condition = new UserCondition(User::class);
        $rule = $condition->createConditionRule(CredentialedConditionRule::class);
        $rule->value = $ruleValue;

        $query = User::find()->id($userModel->id);
        if ($clearStatus) {
            $query->status(null);
        }
        $element = $query->one();

        expect($rule->matchElement($element))->toBe($expected);
    })->with([
        'active user with true' => [['active' => true, 'pending' => false], true, false, true],
        'pending user with true' => [['active' => false, 'pending' => true], true, true, true],
        'inactive non-pending with true' => [['active' => false, 'pending' => false], true, true, false],
    ]);

    it('modifyQuery with value true returns active and pending users', function () {
        $activeUser = UserModel::factory()->create(['active' => true, 'pending' => false]);
        $pendingUser = UserModel::factory()->create(['active' => false, 'pending' => true]);
        $inactiveUser = UserModel::factory()->create(['active' => false, 'pending' => false]);

        $condition = new UserCondition(User::class);
        $rule = $condition->createConditionRule(CredentialedConditionRule::class);
        $rule->value = true;

        $query = User::find()->status(null);
        $rule->modifyQuery($query);

        $resultIds = collect($query->all())->pluck('id')->toArray();

        expect($resultIds)->toContain($activeUser->id);
        expect($resultIds)->toContain($pendingUser->id);
        expect($resultIds)->not->toContain($inactiveUser->id);
    });

    it('modifyQuery with value false returns only inactive users', function () {
        $activeUser = UserModel::factory()->create(['active' => true, 'pending' => false]);
        $inactiveUser = UserModel::factory()->create(['active' => false, 'pending' => false]);

        $condition = new UserCondition(User::class);
        $rule = $condition->createConditionRule(CredentialedConditionRule::class);
        $rule->value = false;

        $query = User::find()->status(null);
        $rule->modifyQuery($query);

        $resultIds = collect($query->all())->pluck('id')->toArray();

        expect($resultIds)->toContain($inactiveUser->id);
        expect($resultIds)->not->toContain($activeUser->id);
    });
});
