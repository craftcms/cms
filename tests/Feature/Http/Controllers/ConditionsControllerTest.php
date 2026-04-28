<?php

declare(strict_types=1);

use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\SlugConditionRule;
use CraftCms\Cms\Element\Conditions\TitleConditionRule;
use CraftCms\Cms\Entry\Conditions\EntryCondition;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Controllers\ConditionsController;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    $this->conditionPayload = function (EntryCondition $condition, array $conditionOverrides = []): array {
        $portableConfig = $condition->getConfig();

        return [
            'config' => Json::encode(array_merge($condition->getBuilderConfig(), [
                'class' => $portableConfig['class'],
                'id' => $condition->id,
                'name' => $condition->name,
                'mainTag' => $condition->mainTag,
                'sortable' => $condition->sortable,
                'forProjectConfig' => $condition->forProjectConfig,
                'addRuleLabel' => $condition->addRuleLabel,
            ])),
            $condition->name => array_merge([
                'class' => $portableConfig['class'],
                'config' => Json::encode($condition->getBuilderConfig()),
                'conditionRules' => $portableConfig['conditionRules'],
            ], $conditionOverrides),
        ];
    };
});

describe('show', function () {
    it('validates that config is required json', function () {
        postJson(action([ConditionsController::class, 'show']))
            ->assertJsonValidationErrorFor('config');
    });

    it('validates the nested condition payload exists', function () {
        $condition = new EntryCondition(Entry::class);
        $condition->id = 'entry-condition';

        $payload = ($this->conditionPayload)($condition);
        unset($payload[$condition->name]);

        postJson(action([ConditionsController::class, 'show']), $payload)
            ->assertJsonValidationErrorFor($condition->name);
    });

    it('validates the nested condition class matches the builder config class', function () {
        $condition = new EntryCondition(Entry::class);
        $condition->id = 'entry-condition';

        $payload = ($this->conditionPayload)($condition, [
            'class' => TitleConditionRule::class,
        ]);

        postJson(action([ConditionsController::class, 'show']), $payload)
            ->assertJsonValidationErrorFor("{$condition->name}.class");
    });

    it('renders the posted condition builder html', function () {
        $condition = new EntryCondition(Entry::class);
        $condition->id = 'entry-condition';

        $rule = $condition->createConditionRule(TitleConditionRule::class);
        $rule->operator = '=';
        $rule->value = 'Hello World';
        $condition->addConditionRule($rule);

        $response = post(action([ConditionsController::class, 'show']), ($this->conditionPayload)($condition));

        $response->assertOk();

        expect($response->getContent())
            ->toContain('condition-main')
            ->toContain('value="Hello World"')
            ->toContain(TitleConditionRule::class);
    });
});

describe('store', function () {
    it('adds the first selectable rule to the condition', function () {
        $condition = new EntryCondition(Entry::class);
        $condition->id = 'entry-condition';

        /** @var ConditionRuleInterface $expectedRule */
        $expectedRule = collect($condition->getSelectableConditionRules())
            ->sortBy(fn (ConditionRuleInterface $rule) => $rule->getLabel())
            ->first();

        $response = post(action([ConditionsController::class, 'store']), ($this->conditionPayload)($condition));

        $response->assertOk();

        expect($expectedRule)->toBeInstanceOf(ConditionRuleInterface::class)
            ->and($response->getContent())
            ->toContain('condition-rule')
            ->toContain($expectedRule::class);
    });
});

describe('destroy', function () {
    it('validates that uid is a uuid', function () {
        $condition = new EntryCondition(Entry::class);

        postJson(action([ConditionsController::class, 'destroy']), array_merge(
            ($this->conditionPayload)($condition),
            ['uid' => 'not-a-uuid'],
        ))->assertJsonValidationErrorFor('uid');
    });

    it('removes the matching rule from the condition', function () {
        $condition = new EntryCondition(Entry::class);
        $condition->id = 'entry-condition';

        $titleRule = $condition->createConditionRule(TitleConditionRule::class);
        $titleRule->operator = '=';
        $titleRule->value = 'Remove me';
        $condition->addConditionRule($titleRule);

        $slugRule = $condition->createConditionRule(SlugConditionRule::class);
        $slugRule->operator = '=';
        $slugRule->value = 'keep-me';
        $condition->addConditionRule($slugRule);

        $response = post(action([ConditionsController::class, 'destroy']), array_merge(
            ($this->conditionPayload)($condition),
            ['uid' => $titleRule->uid],
        ));

        $response->assertOk();

        expect($response->getContent())
            ->not->toContain('value="Remove me"')
            ->toContain('value="keep-me"')
            ->toContain(SlugConditionRule::class);
    });
});
