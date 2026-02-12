<?php

declare(strict_types=1);

use CraftCms\Cms\Condition\BaseSelectConditionRule;

class TestSelectConditionRule extends BaseSelectConditionRule
{
    public function getLabel(): string
    {
        return 'Test Select';
    }

    protected function options(): array
    {
        return [
            'option_a' => 'Option A',
            'option_b' => 'Option B',
            'option_c' => 'Option C',
        ];
    }
}

function callMatchValue(BaseSelectConditionRule $rule, string $value): bool
{
    $ref = new ReflectionMethod($rule, 'matchValue');

    return $ref->invoke($rule, $value);
}

function createSelectRule(string $value = ''): TestSelectConditionRule
{
    $rule = new TestSelectConditionRule;
    $rule->value = $value;

    return $rule;
}

describe('matchValue', function () {
    it('evaluates correctly', function (string $ruleValue, string $testValue, bool $expected) {
        $rule = createSelectRule($ruleValue);

        expect(callMatchValue($rule, $testValue))->toBe($expected);
    })->with([
        'exact match' => ['option_a', 'option_a', true],
        'values differ' => ['option_a', 'option_b', false],
        'default empty string does not match' => ['', 'option_a', false],
        'case-sensitive' => ['option_a', 'Option_A', false],
        'empty string matches empty string' => ['', '', true],
    ]);
});

describe('getConfig', function () {
    it('includes expected keys', function (string $ruleValue, array $expectedKeys) {
        $rule = createSelectRule($ruleValue);
        $config = $rule->getConfig();

        foreach ($expectedKeys as $key => $value) {
            if ($value === null) {
                expect($config)->toHaveKey($key);
            } else {
                expect($config)->toHaveKey($key, $value);
            }
        }
    })->with([
        'value key' => ['option_b', ['value' => 'option_b']],
        'class and uid keys' => ['', ['class' => TestSelectConditionRule::class, 'uid' => null]],
        'empty string as default value' => ['', ['value' => '']],
    ]);

    it('round-trips value through config', function () {
        $rule = createSelectRule('option_c');
        $config = $rule->getConfig();

        unset($config['class']);
        $restored = new TestSelectConditionRule($config);

        expect($restored->value)->toBe('option_c');
        expect($restored->uid)->toBe($rule->uid);
        expect(callMatchValue($restored, 'option_c'))->toBeTrue();
        expect(callMatchValue($restored, 'option_a'))->toBeFalse();
    });
});
