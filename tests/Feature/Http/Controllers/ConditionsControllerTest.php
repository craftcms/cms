<?php

declare(strict_types=1);

use CraftCms\Cms\Condition\ConditionBuilder;
use CraftCms\Cms\Element\Conditions\DateCreatedConditionRule;
use CraftCms\Cms\Element\Conditions\HasUrlConditionRule;
use CraftCms\Cms\Element\Conditions\IdConditionRule;
use CraftCms\Cms\Element\Conditions\SlugConditionRule;
use CraftCms\Cms\Element\Conditions\TitleConditionRule;
use CraftCms\Cms\Entry\Conditions\EntryCondition;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Controllers\ConditionsController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
    $condition = new EntryCondition(Entry::class);
    $builder = app(ConditionBuilder::class)->resolve($condition);
    $this->payload = ['config' => $builder->config, 'value' => $builder->value];
});

it('requires a builder config and portable value', function () {
    postJson(action([ConditionsController::class, 'show']))
        ->assertJsonValidationErrors(['config', 'value']);
});

it('requires matching condition classes', function () {
    $this->payload['value']['class'] = TitleConditionRule::class;

    postJson(action([ConditionsController::class, 'show']), $this->payload)
        ->assertJsonValidationErrorFor('value.class');
});

it('resolves nested and repeated rules into scoped Forms', function () {
    $this->payload['value']['conditionRules'] = [
        'operator' => 'or',
        'rules' => [
            ['class' => TitleConditionRule::class, 'operator' => '=', 'value' => 'Alpha'],
            ['operator' => 'and', 'rules' => [
                ['class' => TitleConditionRule::class, 'operator' => '=', 'value' => 'Beta'],
            ]],
        ],
    ];

    $response = postJson(action([ConditionsController::class, 'show']), $this->payload)
        ->assertOk()
        ->assertJsonPath('builder.value.conditionRules.operator', 'or')
        ->assertJsonPath('builder.value.conditionRules.rules.1.rules.0.value', 'Beta');

    $rules = $response->json('builder.rules');

    expect($rules)->toHaveCount(2);

    foreach ($rules as $uid => $rule) {
        expect($rule['form']['scope'])->toBe(['_conditionRules', $uid])
            ->and($rule['form']['values']['_conditionRules'][$uid]['value'])->toBeIn(['Alpha', 'Beta']);
    }
});

it('creates the selected rule with its Form and assets', function () {
    postJson(action([ConditionsController::class, 'rule']), [
        ...$this->payload,
        'rule' => ['type' => TitleConditionRule::class],
    ])->assertOk()
        ->assertJsonPath('rule.config.class', TitleConditionRule::class)
        ->assertJsonStructure(['rule' => ['form', 'config', 'label'], 'headHtml', 'bodyHtml']);
});

it('preserves compatible values when switching rule types', function () {
    postJson(action([ConditionsController::class, 'rule']), [
        ...$this->payload,
        'rule' => ['class' => TitleConditionRule::class, 'type' => SlugConditionRule::class, 'operator' => 'bw', 'value' => 'keep-me'],
    ])->assertOk()
        ->assertJsonPath('rule.config.class', SlugConditionRule::class)
        ->assertJsonPath('rule.config.operator', 'bw')
        ->assertJsonPath('rule.config.value', 'keep-me');
});

it('rejects invalid rule identity', function (array $rule, string $error) {
    postJson(action([ConditionsController::class, 'rule']), [...$this->payload, 'rule' => $rule])
        ->assertJsonValidationErrorFor($error);
})->with([
    'invalid class' => [['type' => stdClass::class], 'rule'],
    'invalid uid' => [['class' => TitleConditionRule::class, 'uid' => 'not-a-uuid'], 'rule.uid'],
]);

it('validates nested rule values on apply', function (array $config, ?string $attribute) {
    $uid = (string) Str::uuid();
    $this->payload['value']['conditionRules'] = ['operator' => 'or', 'rules' => [
        ['operator' => 'and', 'rules' => [['uid' => $uid, ...$config]]],
    ]];

    $response = postJson(action([ConditionsController::class, 'validate']), $this->payload);

    if ($attribute !== null) {
        $response->assertJsonValidationErrorFor("_conditionRules.$uid.$attribute");
    } else {
        $response->assertOk()->assertJsonPath('valid', true);
    }
})->with([
    'invalid operator' => [['class' => TitleConditionRule::class, 'operator' => 'invalid'], 'operator'],
    'invalid range maximum' => [['class' => IdConditionRule::class, 'operator' => 'between', 'maxValue' => 'invalid'], 'maxValue'],
    'unused range maximum' => [['class' => IdConditionRule::class, 'operator' => 'empty', 'maxValue' => 'invalid'], null],
    'empty text operator' => [['class' => TitleConditionRule::class, 'operator' => 'empty'], null],
    'date without an operator' => [['class' => DateCreatedConditionRule::class], null],
    'boolean without an operator' => [['class' => HasUrlConditionRule::class], null],
]);
