<?php

declare(strict_types=1);

use CraftCms\Cms\Component\Kinds\ComponentKinds;
use CraftCms\Cms\Component\Kinds\TypeRegistryKind;
use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Element\Conditions\SlugConditionRule;
use CraftCms\Cms\Entry\Conditions\EntryCondition;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Heading;
use CraftCms\Cms\FieldLayout\LayoutElements\Tip;
use CraftCms\Cms\FieldLayout\LayoutElements\Users\EmailField;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\Models\UserGroup;
use CraftCms\Cms\Workflow\UserReview\UserReviewStage;
use CraftCms\Cms\Workflow\WorkflowStageTypes;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;

beforeEach(function (): void {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:admin'], 'craft-mcp');

    $this->call = fn (string $name, array $arguments): TestResponse => McpRequest::send($this, 'tools/call', [
        'name' => $name,
        'arguments' => $arguments,
    ])->assertOk();
    $this->result = fn (string $name, array $arguments): array => ($this->call)($name, $arguments)
        ->assertJsonPath('result.isError', false)
        ->json('result.structuredContent');
});

it('describes workflow stage types and the values their settings accept', function (): void {
    $group = UserGroup::factory()->create(['name' => 'Editors']);

    $types = ($this->result)('component-types.list', ['kind' => 'workflow-stage'])['types'];
    $settings = ($this->result)('component-types.get', ['kind' => 'workflow-stage', 'type' => UserReviewStage::class])['settingsSchema'];

    expect($types)->toContain(['type' => UserReviewStage::class, 'name' => 'User Review'])
        ->and($settings['required'])->toEqualCanonicalizing(['userGroups', 'approvalsRequired', 'approvalMode'])
        ->and($settings['properties']['userGroups']['items']['oneOf'])->toContain(['const' => $group->uid, 'title' => 'Editors'])
        ->and($settings['properties']['approvalsRequired'])->toMatchArray(['type' => 'number', 'minimum' => 1, 'default' => 1])
        ->and(array_column($settings['properties']['approvalMode']['oneOf'], 'const'))->toBe(['total', 'per-group']);
});

it('describes field types and Asset Transform drivers', function (): void {
    $fieldTypes = array_column(($this->result)('component-types.list', ['kind' => 'field'])['types'], 'type');
    $plainText = ($this->result)('component-types.get', ['kind' => 'field', 'type' => PlainText::class])['settingsSchema']['properties'];
    $driver = ($this->result)('component-types.get', ['kind' => 'asset-transform-driver', 'type' => 'craft']);

    expect($fieldTypes)->toContain(PlainText::class)
        ->and($plainText['multiline'])->toMatchArray(['type' => 'boolean', 'default' => false])
        ->and(array_column($plainText['uiMode']['oneOf'], 'const'))->toBe(['normal', 'enlarged'])
        ->and($driver['settingsSchema']['properties']['hasUrls']['type'])->toBe('boolean')
        ->and($driver)->toHaveKey('parametersSchema');

    ($this->call)('component-types.get', ['kind' => 'field', 'type' => 'Nope'])
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'Unknown field type [Nope]. Call component-types.list for the available types.');
});

it('describes the elements an element type’s field layouts accept', function (): void {
    $types = collect(($this->result)('component-types.list', ['kind' => 'field-layout-element', 'scope' => 'users'])['types'])->keyBy('type');
    $warning = ($this->result)('component-types.get', ['kind' => 'field-layout-element', 'scope' => 'users', 'type' => Tip::class.'(style: warning)']);
    $customField = ($this->result)('component-types.get', ['kind' => 'field-layout-element', 'scope' => 'users', 'type' => CustomField::class])['settingsSchema']['properties'];

    expect($types[EmailField::class.'(attribute: email)']['config'])->toBe(['type' => EmailField::class, 'attribute' => 'email'])
        ->and($types)->toHaveKeys([CustomField::class, Heading::class, Tip::class.'(style: tip)'])
        ->and($warning['config'])->toBe(['type' => Tip::class, 'style' => 'warning'])
        ->and($warning['settingsSchema']['properties']['tip']['type'])->toBe('string')
        ->and($customField)->toHaveKeys(['label', 'instructions', 'userCondition', 'elementCondition'])
        ->and($customField['required'])->toMatchArray(['type' => 'boolean', 'default' => false])
        ->and($customField['width']['enum'])->toContain(25, 50, 100);

    ($this->call)('component-types.list', ['kind' => 'field-layout-element'])
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'Provide scope: The element type handle or class whose field layouts the elements go in, such as entries.');
});

it('describes the rules a condition accepts and points condition settings to them', function (): void {
    $rules = collect(($this->result)('component-types.list', ['kind' => 'condition-rule', 'scope' => EntryCondition::class])['types'])->keyBy('type');
    $slug = ($this->result)('component-types.get', ['kind' => 'condition-rule', 'scope' => EntryCondition::class, 'type' => SlugConditionRule::class])['settingsSchema']['properties'];
    $selectionCondition = ($this->result)('component-types.get', ['kind' => 'field', 'type' => Entries::class])['settingsSchema']['properties']['selectionCondition'];

    expect($rules[SlugConditionRule::class])->toMatchArray(['name' => 'Slug', 'config' => ['class' => SlugConditionRule::class]])
        ->and(array_column($slug['operator']['oneOf'], 'const'))->toContain('=', 'bw', 'empty')
        ->and($slug['value']['type'])->toBe('string')
        ->and($selectionCondition['description'])->toContain('kind: condition-rule and scope: '.EntryCondition::class)
        ->and($selectionCondition['properties']['class'])->toBe(['const' => EntryCondition::class])
        ->and($selectionCondition['properties']['conditionRules']['properties']['operator']['enum'])->toBe(['and', 'or']);

    ($this->call)('component-types.list', ['kind' => 'condition-rule', 'scope' => 'Nope'])
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'Provide scope: the condition class, which settings schemas name.');
});

it('lists the component kinds, including those plugins register', function (): void {
    app(ComponentKinds::class)->register(WidgetStageKind::class);

    $kinds = collect(($this->result)('component-types.list', [])['kinds'])->keyBy('kind');
    $types = ($this->result)('component-types.list', ['kind' => 'widget-stage'])['types'];
    $schema = ($this->result)('component-types.get', ['kind' => 'widget-stage', 'type' => UserReviewStage::class])['settingsSchema'];

    expect($kinds->keys()->all())->toContain('field', 'workflow-stage', 'asset-transform-driver', 'field-layout-element', 'condition-rule', 'widget-stage')
        ->and($kinds['field-layout-element']['scope'])->toContain('element type')
        ->and($kinds['field'])->not->toHaveKey('scope')
        ->and($types)->toBe([['type' => UserReviewStage::class, 'name' => 'User Review']])
        ->and($schema['properties'])->toHaveKey('approvalMode');

    ($this->call)('component-types.list', ['kind' => 'nope'])
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', fn (string $text): bool => str_starts_with($text, 'Unknown component kind [nope]. Available kinds: field, '));
});

readonly class WidgetStageKind extends TypeRegistryKind
{
    public static function handle(): string
    {
        return 'widget-stage';
    }

    public function description(): string
    {
        return 'Widget stages.';
    }

    public function usage(): string
    {
        return 'Use them as widget stages.';
    }

    protected function registry(): TypeRegistry
    {
        $registry = new WorkflowStageTypes;
        $registry->remove(...$registry->types()->reject(fn (string $type): bool => $type === UserReviewStage::class)->all());

        return $registry;
    }
}
