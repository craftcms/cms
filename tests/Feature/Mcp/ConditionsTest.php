<?php

declare(strict_types=1);

use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Conditions\TitleConditionRule;
use CraftCms\Cms\Entry\Conditions\SectionConditionRule;
use CraftCms\Cms\Field\Conditions\TextFieldConditionRule;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Conditions\UserCondition;
use CraftCms\Cms\User\Models\User;
use Laravel\Passport\Passport;

beforeEach(function (): void {
    Edition::set(Edition::Pro);
    config()->set('passport.public_key', openssl_pkey_get_details(passportKey())['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:admin'], 'craft-mcp');
    $this->call = fn (string $tool, array $arguments): array => McpRequest::send($this, 'tools/call', [
        'name' => $tool,
        'arguments' => $arguments,
    ])->assertOk()->json('result');
    $this->structured = function (string $tool, array $arguments): array {
        $result = ($this->call)($tool, $arguments);
        expect($result['isError'] ?? false)->toBeFalse(json_encode($result));

        return $result['structuredContent'];
    };
    $this->inLayout = Field::factory()->create(['name' => 'Subtitle', 'handle' => 'subtitle', 'type' => PlainText::class]);
    $this->elsewhere = Field::factory()->create(['name' => 'Elsewhere', 'handle' => 'elsewhere', 'type' => PlainText::class]);
    Fields::refreshFields();
    $this->entryType = ($this->structured)('configuration_create', ['type' => 'entry-types', 'attributes' => [
        'name' => 'Conditional',
        'handle' => 'conditional',
        'fieldLayout' => ['tabs' => [['name' => 'Content', 'elements' => [
            ['type' => CustomField::class, 'fieldUid' => $this->inLayout->uid],
        ]]]],
    ]])['item'];
});

it('lists the rules a condition accepts where it’s used', function (): void {
    $classes = fn (array $arguments): array => array_column(($this->structured)('condition_schema', $arguments)['rules'], 'class');
    $layoutUid = $this->entryType['fieldLayout']['uid'];
    $layout = ($this->structured)('condition_schema', ['context' => 'field-layout', 'fieldLayout' => $layoutUid]);
    $fieldUids = array_values(array_filter(array_map(fn (array $rule) => $rule['template']['fieldUid'] ?? null, $layout['rules'])));

    expect($classes(['context' => 'element-sources', 'elementType' => 'entries']))->toContain(SectionConditionRule::class)
        ->and($fieldUids)->toBe([$this->inLayout->uid])
        ->and(($this->structured)('condition_schema', ['context' => 'field-layout', 'fieldLayout' => $layoutUid, 'condition' => 'user'])['conditionClass'])->toBe(UserCondition::class)
        ->and($classes(['context' => 'relation-selection', 'fieldType' => Entries::class]))->toContain(TitleConditionRule::class);
});

it('rejects field layout visibility rules Craft would drop, and saves valid ones', function (array $rule, string $message): void {
    $result = ($this->call)('configuration_update', [
        'type' => 'entry-types',
        'identifier' => ['handle' => 'conditional'],
        'attributes' => ['fieldLayout' => ['tabs' => [['name' => 'Content', 'elements' => [
            ['type' => CustomField::class, 'fieldUid' => $this->inLayout->uid, 'elementCondition' => ['conditionRules' => [$rule]]],
        ]]]]],
    ]);

    expect($result['isError'] ?? false)->toBeTrue()
        ->and($result['content'][0]['text'])->toContain($message);
})->with([
    'field outside the layout' => [fn () => ['class' => TextFieldConditionRule::class, 'fieldUid' => test()->elsewhere->uid, 'operator' => '=', 'value' => 'x'], 'not included in the available field layouts'],
    'unknown setting' => [['class' => TitleConditionRule::class, 'operator' => '=', 'value' => 'x', 'text' => 'x'], 'text'],
]);

it('saves field layout visibility conditions and accepts the layout reads return', function (): void {
    $update = fn (array $fieldLayout): array => ($this->structured)('configuration_update', [
        'type' => 'entry-types',
        'identifier' => ['handle' => 'conditional'],
        'attributes' => ['fieldLayout' => $fieldLayout],
    ])['item']['fieldLayout'];

    $saved = $update(['tabs' => [['name' => 'Content', 'elements' => [
        ['type' => CustomField::class, 'fieldUid' => $this->inLayout->uid, 'elementCondition' => ['conditionRules' => [
            ['class' => TextFieldConditionRule::class, 'fieldUid' => $this->inLayout->uid, 'operator' => '=', 'value' => 'x'],
        ]]],
    ]]]]);
    $rules = $saved['config']['tabs'][0]['elements'][0]['elementCondition']['conditionRules']['rules'];
    $resaved = $update($saved);

    expect([$rules[0]['class'], $rules[0]['value']])->toBe([TextFieldConditionRule::class, 'x'])
        ->and($resaved['config']['tabs'][0]['elements'][0]['elementCondition']['conditionRules']['rules'])->toBe($rules);
});

it('validates relation field selection conditions and keeps them when sent back', function (): void {
    $create = fn (array $rule): array => ($this->call)('configuration_create', ['type' => 'fields', 'attributes' => [
        'type' => Entries::class,
        'name' => 'Related',
        'handle' => 'related',
        'settings' => ['selectionCondition' => ['conditionRules' => [$rule]]],
    ]]);

    $rejected = $create(['class' => TitleConditionRule::class, 'operator' => 'nope', 'value' => 'news']);
    $saved = $create(['class' => TitleConditionRule::class, 'operator' => '**', 'value' => 'news']);
    $read = $saved['structuredContent']['item']['settings']['selectionCondition'];
    $rule = $read['conditionRules']['rules'][0];
    ($this->structured)('configuration_update', [
        'type' => 'fields',
        'identifier' => ['handle' => 'related'],
        'attributes' => ['settings' => ['selectionCondition' => $read]],
    ]);
    Fields::refreshFields();

    expect($rejected['isError'])->toBeTrue()
        ->and($rejected['content'][0]['text'])->toContain('settings.selectionCondition.conditionRules[0]')
        ->and($saved['isError'] ?? false)->toBeFalse()
        ->and([$rule['class'], $rule['operator'], $rule['value']])->toBe([TitleConditionRule::class, '**', 'news'])
        ->and(Fields::getFieldByHandle('related')->getSelectionCondition()->getConditionRules()->getRules())->toHaveCount(1);
});
