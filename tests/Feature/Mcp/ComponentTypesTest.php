<?php

declare(strict_types=1);

use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Heading;
use CraftCms\Cms\FieldLayout\LayoutElements\Tip;
use CraftCms\Cms\FieldLayout\LayoutElements\Users\EmailField;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\Models\UserGroup;
use CraftCms\Cms\Workflow\UserReview\UserReviewStage;
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
        ->assertJsonPath('result.content.0.text', 'Provide scope: the element type whose field layouts the elements go in, such as entries.');
});
