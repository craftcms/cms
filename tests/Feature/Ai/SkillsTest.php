<?php

declare(strict_types=1);

use CraftCms\Cms\Ai\Assistant\AssistantAgent;
use CraftCms\Cms\Ai\Http\Controllers\AssistantController;
use CraftCms\Cms\Ai\Http\Controllers\SkillsController;
use CraftCms\Cms\Ai\Models\AgentSkill;
use CraftCms\Cms\Ai\Settings;
use CraftCms\Cms\Ai\Tasks\Assistant;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Str;
use Laravel\Ai\Models\ConversationMessage;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Skills\Skill;

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
