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

it('saves the guidance for every site and for each site from the utility, and removes emptied guidance', function () {
    postJson(action([GuidanceController::class, 'store']), ['guidance' => [
        ['siteId' => null, 'body' => ' Write plainly. '],
        ['siteId' => $this->siteId, 'body' => 'Use US spelling.'],
    ]])->assertOk();

    expect(app(Guidance::class))
        ->forAllSites()->toBe('Write plainly.')
        ->forSite($this->siteId)->toBe('Use US spelling.');

    postJson(action([GuidanceController::class, 'store']), ['guidance' => [
        ['siteId' => null, 'body' => 'Write plainly.'],
        ['siteId' => $this->siteId, 'body' => ''],
    ]])->assertOk();

    expect(AgentGuidance::query()->pluck('body')->all())->toBe(['Write plainly.']);
});

it('only saves the guidance for sites the user can edit, and for every site if they can edit every site', function () {
    $other = Site::factory()->create();
    Sites::refreshSites();
    $user = UserModel::factory()->withPermissions([
        'accessCp',
        'accessUtilities',
        'utility:ai-guidance',
        'editSite:'.Sites::getPrimarySite()->uid,
    ])->create(['admin' => false]);
    actingAs($user->asElement());

    postJson(action([GuidanceController::class, 'store']), ['guidance' => [
        ['siteId' => $this->siteId, 'body' => 'Use US spelling.'],
        ['siteId' => $other->id, 'body' => 'Use British spelling.'],
        ['siteId' => null, 'body' => 'Write plainly.'],
    ]])->assertJsonValidationErrors(['guidance.1.siteId', 'guidance.2.siteId']);

    expect(AgentGuidance::query()->exists())->toBeFalse()
        ->and(array_column(AiGuidance::scopes(), 'editable', 'siteId'))->toBe(['' => false, $this->siteId => true, $other->id => false]);
});

it('only lets users who can use the utility save site guidance', function () {
    $user = UserModel::factory()->withPermissions(['accessCp', 'accessUtilities'])->create(['admin' => false]);
    actingAs($user->asElement());

    postJson(action([GuidanceController::class, 'store']), ['guidance' => [['siteId' => null, 'body' => 'Write plainly.']]])->assertForbidden();

    expect(AgentGuidance::query()->exists())->toBeFalse();
});

it('keeps showing a single site’s own guidance, which the assistant still follows', function () {
    expect(array_column(AiGuidance::scopes(), 'heading'))->toBe(['Guidance']);

    app(Guidance::class)->save(siteId: $this->siteId, userId: null, body: 'Use US spelling.');

    expect(array_column(AiGuidance::scopes(), 'siteId'))->toBe([null, $this->siteId]);
});

it('only gives users who can use the assistant the account screen for their own guidance', function () {
    actingAs(UserModel::factory()->withPermissions(['accessCp'])->create(['admin' => false])->asElement());

    getJson(action([AccountController::class, 'index']))->assertForbidden();
    patchJson(action([AccountController::class, 'update']), ['guidance' => 'Reply in Dutch.'])->assertForbidden();

    expect(AgentGuidance::query()->exists())->toBeFalse();
});

it('saves a user’s own guidance on their account', function () {
    $user = UserModel::factory()->withPermissions(['accessCp', Assistant::PERMISSION])->create(['admin' => false]);
    actingAs($user->asElement());

    patchJson(action([AccountController::class, 'update']), ['guidance' => 'Reply in Dutch.'])->assertOk();

    expect(app(Guidance::class)->forUser($user->id))->toBe('Reply in Dutch.')
        ->and(app(Guidance::class)->forAllSites())->toBeNull();
});

it('gives the assistant the guidance for every site, for the site the user is working in, and the user’s', function (bool $editorOpen, bool $controlPanelSite, ?string $siteGuidance) {
    $other = Site::factory()->create();
    Sites::refreshSites();
    $guidance = app(Guidance::class);
    $guidance->save(siteId: null, userId: null, body: 'Write plainly.');
    $guidance->save(siteId: $this->siteId, userId: null, body: 'Use US spelling.');
    $guidance->save(siteId: $other->id, userId: null, body: 'Use British spelling.');
    $guidance->save(siteId: null, userId: $this->admin->id, body: 'Reply in Dutch.');
    $entry = EntryModel::factory()->createElement(['title' => 'Lighthouses']);
    AssistantAgent::fake(['Done.']);

    postJson(action([AssistantController::class, 'run']), [
        'threadId' => (string) Str::uuid(),
        'runId' => (string) Str::uuid(),
        'messages' => [['id' => (string) Str::uuid(), 'role' => 'user', 'content' => 'Write a teaser']],
        'forwardedProps' => [
            'context' => $editorOpen ? [[
                'type' => 'craft:element',
                'ref' => 'editor-1',
                'open' => true,
                'elementType' => Entry::class,
                'elementId' => $entry->id,
                'siteId' => $entry->siteId,
                'namespace' => [],
            ]] : [],
            'siteId' => $controlPanelSite ? $other->id : null,
        ],
    ])->assertOk()->streamedContent();

    AssistantAgent::assertPrompted(function (AgentPrompt $prompt) use ($siteGuidance): bool {
        $instructions = $prompt->agent->instructions();
        $included = array_values(array_filter(
            ['Use US spelling.', 'Use British spelling.'],
            static fn (string $body): bool => str_contains($instructions, $body),
        ));

        return str_contains($instructions, 'Write plainly.')
            && str_contains($instructions, 'Reply in Dutch.')
            && $included === array_filter([$siteGuidance])
            && ! str_contains($instructions, 'Before writing or editing content, read guidance');
    });
})->with([
    'the open element’s site, over the control panel’s' => [true, true, 'Use US spelling.'],
    'the control panel’s site, without an element open' => [false, true, 'Use British spelling.'],
    'no site' => [false, false, null],
]);

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
