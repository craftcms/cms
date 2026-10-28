<?php

declare(strict_types=1);

use CraftCms\Cms\Ai\Assistant\AssistantAgent;
use CraftCms\Cms\Ai\Guidance\Guidance;
use CraftCms\Cms\Ai\Http\Controllers\AccountController;
use CraftCms\Cms\Ai\Http\Controllers\AssistantController;
use CraftCms\Cms\Ai\Http\Controllers\GuidanceController;
use CraftCms\Cms\Ai\Models\AgentGuidance;
use CraftCms\Cms\Ai\Settings;
use CraftCms\Cms\Ai\Tasks\Assistant;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\Utility\Utilities\AiGuidance;
use Illuminate\Support\Str;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Passport\Passport;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    config(['ai.providers' => ['anthropic' => ['driver' => 'anthropic', 'key' => 'sk-test']]]);
    app()->instance(Settings::class, new Settings([
        'defaults' => ['text' => ['provider' => 'anthropic']],
    ]));
    Edition::set(Edition::Pro);
    $this->admin = UserModel::factory()->create(['admin' => true]);
    actingAs($this->admin->asElement());

    $this->siteId = Sites::getPrimarySite()->id;
});

it('returns the guidance to MCP clients, for a site the user can edit', function () {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    $user = UserModel::factory()->withPermissions(['accessCp', 'useCraftMcp', 'editSite:'.Sites::getPrimarySite()->uid])->create(['admin' => false]);
    Passport::actingAs($user, ['mcp:read'], 'craft-mcp');

    $guidance = app(Guidance::class);
    $guidance->save(siteId: null, userId: null, body: 'Write plainly.');
    $guidance->save(siteId: $this->siteId, userId: null, body: 'Use US spelling.');
    $guidance->save(siteId: null, userId: $user->id, body: 'Reply in Dutch.');

    McpRequest::send($this, 'tools/call', ['name' => 'guidance.get', 'arguments' => ['siteId' => $this->siteId]])
        ->assertOk()
        ->assertJsonPath('result.structuredContent', [
            'allSites' => 'Write plainly.',
            'site' => 'Use US spelling.',
            'user' => 'Reply in Dutch.',
        ]);

    $other = Site::factory()->create();
    Sites::refreshSites();
    $guidance->save(siteId: $other->id, userId: null, body: 'Use British spelling.');

    McpRequest::send($this, 'tools/call', ['name' => 'guidance.get', 'arguments' => ['siteId' => $other->id]])
        ->assertOk()
        ->assertJsonPath('result.isError', true)
        ->assertDontSee('Use British spelling.');
});
