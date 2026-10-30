<?php

declare(strict_types=1);

use CraftCms\Cms\Ai\Assistant\AssistantAgent;
use CraftCms\Cms\Ai\Assistant\McpTools;
use CraftCms\Cms\Ai\Http\Controllers\AssistantController;
use CraftCms\Cms\Ai\Http\Controllers\SkillsController;
use CraftCms\Cms\Ai\Models\AgentSkill;
use CraftCms\Cms\Ai\Settings;
use CraftCms\Cms\Ai\Tasks\Assistant;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Str;
use Laravel\Ai\Models\ConversationMessage;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Skills\Skill;
use Laravel\Passport\Passport;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    config(['ai.providers' => ['anthropic' => ['driver' => 'anthropic', 'key' => 'sk-test']]]);
    app()->instance(Settings::class, new Settings([
        'defaults' => ['text' => ['provider' => 'anthropic']],
    ]));
    Edition::set(Edition::Pro);

    actingAs(UserModel::factory()->create(['admin' => true])->asElement());
});

/**
 * Signs an MCP client in with write access, as a user who can edit the primary site.
 *
 * @param  list<string>  $permissions
 */
function mcpUser(array $permissions): void
{
    enableMcp();
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    $user = UserModel::factory()->withPermissions(['accessCp', 'useCraftMcp', 'editSite:'.Sites::getPrimarySite()->uid, ...$permissions])->create(['admin' => false]);
    Passport::actingAs($user, ['mcp:write'], 'craft-mcp');
}

function skill(string $title): AgentSkill
{
    return AgentSkill::query()->create([
        'title' => $title,
        'description' => "Use for $title.",
        'body' => "Instructions for $title.",
    ]);
}

it('creates, edits, and deletes skills from the utility, refusing a title another skill has', function () {
    postJson(action([SkillsController::class, 'store']), [
        'title' => 'Voice',
        'description' => 'Use when writing copy.',
        'body' => 'Write in a warm, plain voice.',
    ])->assertOk()->assertJsonPath('skill.title', 'Voice');

    $skill = AgentSkill::query()->sole();

    postJson(action([SkillsController::class, 'store']), [
        'title' => 'Voice',
        'description' => 'Another voice.',
        'body' => 'Write in another voice.',
    ])->assertJsonValidationErrors('title');

    patchJson(action([SkillsController::class, 'update'], ['skillId' => $skill->id]), [
        'title' => 'Voice',
        'description' => 'Use when writing copy.',
        'body' => 'Write in a calm voice.',
    ])->assertOk();

    expect($skill->fresh()->body)->toBe('Write in a calm voice.');

    getJson(action([SkillsController::class, 'index']))
        ->assertOk()
        ->assertJsonPath('skills.0.body', 'Write in a calm voice.');

    deleteJson(action([SkillsController::class, 'destroy'], ['skillId' => $skill->id]))->assertOk();

    expect(AgentSkill::query()->exists())->toBeFalse();
});

it('goes on to edit a skill created from a page, so saving again updates it', function () {
    post(action([SkillsController::class, 'store']), [
        'title' => 'Voice',
        'description' => 'Use when writing copy.',
        'body' => 'Write in a warm, plain voice.',
    ])->assertRedirect(Url::cpUrl('ai-skills/'.AgentSkill::query()->sole()->id));
});

it('only lets users who can use the utility manage skills', function () {
    $skill = skill('Voice');
    actingAs(UserModel::factory()->withPermissions(['accessCp', 'accessUtilities', Assistant::PERMISSION])->create(['admin' => false])->asElement());

    postJson(action([SkillsController::class, 'store']), ['title' => 'Teaser', 'description' => 'Use for teasers.', 'body' => 'Write a teaser.'])->assertForbidden();
    deleteJson(action([SkillsController::class, 'destroy'], ['skillId' => $skill->id]))->assertForbidden();
    getJson(action([SkillsController::class, 'index']))->assertForbidden();

    expect(AgentSkill::query()->pluck('title')->all())->toBe(['Voice']);
});

it('offers the skills whose titles match what the user typed after /', function () {
    skill('Teaser');
    skill('Alt text');
    skill('Translation');

    getJson(action([AssistantController::class, 'skills'], ['query' => '/te']))
        ->assertOk()
        ->assertJsonPath('*.label', ['Alt text', 'Teaser']);
});

it('offers the assistant every skill to load, and follows the skills the user chose', function () {
    $loadable = skill('Voice');
    $chosen = skill('Teaser');
    AssistantAgent::fake(['Done.']);

    postJson(action([AssistantController::class, 'run']), [
        'threadId' => (string) Str::uuid(),
        'runId' => (string) Str::uuid(),
        'messages' => [['id' => (string) Str::uuid(), 'role' => 'user', 'content' => 'Write a teaser']],
        'forwardedProps' => ['context' => [
            ['type' => 'craft:skill', 'ref' => 'ref-1', 'skillId' => $chosen->id],
        ]],
    ])->assertOk()->streamedContent();

    AssistantAgent::assertPrompted(function (AgentPrompt $prompt) use ($loadable, $chosen): bool {
        $descriptions = collect($prompt->agent->skills())->mapWithKeys(fn (Skill $skill): array => [$skill->name => $skill->description]);
        $instructions = $prompt->agent->instructions();

        return $descriptions->all() == [$loadable->name() => $loadable->description, $chosen->name() => $chosen->description]
            && str_contains($instructions, "<skill_content name=\"{$chosen->name()}\">\n{$chosen->body}")
            && ! str_contains($instructions, $loadable->body);
    });
});

it('shows the chat which skill the model loaded, by its title, while the model gets its instructions', function () {
    $skill = skill('Voice');
    $threadId = (string) Str::uuid();
    AssistantAgent::fake([new ToolCall('call_1', 'LoadSkill', ['name' => $skill->name()]), 'Done.']);

    $content = postJson(action([AssistantController::class, 'run']), [
        'threadId' => $threadId,
        'runId' => (string) Str::uuid(),
        'messages' => [['id' => (string) Str::uuid(), 'role' => 'user', 'content' => 'Write a teaser']],
    ])->assertOk()->streamedContent();

    preg_match_all('/^data: (.+)$/m', $content, $lines);
    $result = collect($lines[1])->map(fn (string $line): mixed => Json::decode($line))->firstWhere('type', 'TOOL_CALL_RESULT');
    $stored = ConversationMessage::query()->where('role', 'assistant')->sole()->tool_results[0]['result'];
    $reloaded = getJson(action([AssistantController::class, 'show'], ['threadId' => $threadId]))->json('messages.1.parts.0.output');

    expect(Json::decode($result['content']))->toBe(['skill' => 'Voice'])
        ->and(Json::decode($reloaded))->toBe(['skill' => 'Voice'])
        ->and($stored)->toContain($skill->body);
});

it('shows the chat the error when the model loads a skill that doesn’t exist, rather than a loaded skill', function () {
    skill('Voice');
    AssistantAgent::fake([new ToolCall('call_1', 'LoadSkill', ['name' => 'deleted-999']), 'Done.']);

    $content = postJson(action([AssistantController::class, 'run']), [
        'threadId' => (string) Str::uuid(),
        'runId' => (string) Str::uuid(),
        'messages' => [['id' => (string) Str::uuid(), 'role' => 'user', 'content' => 'Write a teaser']],
    ])->assertOk()->streamedContent();

    preg_match_all('/^data: (.+)$/m', $content, $lines);
    $result = collect($lines[1])->map(fn (string $line): mixed => Json::decode($line))->firstWhere('type', 'TOOL_CALL_RESULT');

    expect($result['content'])->toContain('deleted-999')
        ->and(json_decode($result['content'], true))->not->toHaveKey('skill');
});

it('lets MCP clients create, edit, and delete skills, refusing a title another skill has', function () {
    mcpUser(['accessUtilities', 'utility:ai-guidance']);
    $voice = skill('Voice');

    $created = McpRequest::send($this, 'tools/call', ['name' => 'skills.create', 'arguments' => ['title' => 'Teaser', 'description' => 'Use for teasers.', 'instructions' => 'Write a teaser.']])
        ->assertOk()
        ->assertJsonPath('result.structuredContent.instructions', 'Write a teaser.');

    expect($created->json('result.structuredContent.cpEditUrl'))->toBe(Url::cpUrl('ai-skills/'.$created->json('result.structuredContent.id')));
    McpRequest::send($this, 'tools/call', ['name' => 'skills.update', 'arguments' => ['id' => $voice->id, 'description' => 'Use for anything written.']])
        ->assertOk()
        ->assertJsonPath('result.isError', false);

    expect(AgentSkill::query()->orderBy('title')->pluck('description', 'title')->all())->toBe([
        'Teaser' => 'Use for teasers.',
        'Voice' => 'Use for anything written.',
    ])
        ->and($voice->refresh()->body)->toBe('Instructions for Voice.');

    McpRequest::send($this, 'tools/call', ['name' => 'skills.update', 'arguments' => ['id' => $voice->id, 'title' => 'Teaser']])
        ->assertOk()
        ->assertJsonPath('result.isError', true);
    McpRequest::send($this, 'tools/call', ['name' => 'skills.delete', 'arguments' => ['id' => $voice->id]])
        ->assertOk()
        ->assertJsonPath('result.structuredContent', ['deleted' => true, 'id' => $voice->id]);

    expect(AgentSkill::query()->pluck('title')->all())->toBe(['Teaser']);
});

it('only links MCP clients to edit skills if the user can use the control panel', function () {
    enableMcp();
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    $user = UserModel::factory()->withPermissions(['useCraftMcp', 'accessUtilities', 'utility:ai-guidance'])->create(['admin' => false]);
    Passport::actingAs($user, ['mcp:write'], 'craft-mcp');

    McpRequest::send($this, 'tools/call', ['name' => 'skills.create', 'arguments' => ['title' => 'Teaser', 'description' => 'Use for teasers.', 'instructions' => 'Write a teaser.']])
        ->assertOk()
        ->assertJsonPath('result.structuredContent.title', 'Teaser')
        ->assertJsonPath('result.structuredContent.cpEditUrl', null);
});

it('only offers MCP clients the skills to manage, and links to edit them, if the user can use the utility', function () {
    mcpUser([]);
    skill('Voice');

    $tools = McpRequest::send($this, 'tools/list')->assertOk()->json('result.tools.*.name');

    McpRequest::send($this, 'tools/call', ['name' => 'skills.list', 'arguments' => []])
        ->assertOk()
        ->assertJsonPath('result.structuredContent.skills.0.cpEditUrl', null);
    expect($tools)->toContain('skills.list')
        ->not->toContain('skills.create')
        ->not->toContain('skills.update')
        ->not->toContain('skills.delete');
});

it('lets the assistant manage skills, but not load them as MCP clients do', function () {
    request()->setUserResolver(static fn () => auth()->user());
    $tools = app(McpTools::class);

    expect($tools->call('skills_create', ['title' => 'Teaser', 'description' => 'Use for teasers.', 'instructions' => 'Write a teaser.']))->not->toStartWith('Error:')
        ->and($tools->call('skills_list', []))->toStartWith('Error: There’s no tool called skills_list.')
        ->and(AgentSkill::query()->pluck('title')->all())->toBe(['Teaser']);
});
