<?php

declare(strict_types=1);

use CraftCms\Cms\Ai\Assistant\AssistantAgent;
use CraftCms\Cms\Ai\Assistant\Content\ViewEditorImage;
use CraftCms\Cms\Ai\CapabilityChecks;
use CraftCms\Cms\Ai\Data\CapabilityCheck;
use CraftCms\Cms\Ai\Data\ResolvedProvider;
use CraftCms\Cms\Ai\Enums\Capability;
use CraftCms\Cms\Ai\Http\Controllers\AssistantController;
use CraftCms\Cms\Ai\ModelImages;
use CraftCms\Cms\Ai\Settings;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Ai\Files\StoredImage;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Tools\Request as ToolRequest;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    config(['ai.providers' => ['anthropic' => ['driver' => 'anthropic', 'key' => 'sk-test']]]);
    app()->instance(Settings::class, new Settings([
        'defaults' => ['text' => ['provider' => 'anthropic']],
    ]));
    Edition::set(Edition::Pro);
    actingAs(UserModel::factory()->create(['admin' => true])->asElement());

    config()->set('filesystems.disks.assistant-images', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/assistant-images'),
    ]);
    Storage::disk('assistant-images')->deleteDirectory('');
    $volume = Volume::factory()->create(['fs' => 'assistant-images']);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);

    $image = imagecreatetruecolor(2000, 1000);
    ob_start();
    imagejpeg($image);
    Storage::disk('assistant-images')->put('puck.jpg', (string) ob_get_clean());

    $this->asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
        'filename' => 'puck.jpg',
        'kind' => 'image',
        'width' => 2000,
        'height' => 1000,
    ]);
});

/** Says whether the assistant’s model has vision, as the AI settings checked it. */
function withVision(bool $vision): void
{
    app()->instance(CapabilityChecks::class, new class($vision) extends CapabilityChecks
    {
        public function __construct(private readonly bool $vision) {}

        public function result(Capability $capability, ResolvedProvider $resolved): CapabilityCheck
        {
            return new CapabilityCheck($capability, $resolved->provider, 'model', $capability !== Capability::Vision || $this->vision);
        }
    });
}

function chatWithOpenAsset(Asset $asset, string $threadId, string $message): TestResponse
{
    return postJson(action([AssistantController::class, 'run']), [
        'threadId' => $threadId,
        'runId' => (string) Str::uuid(),
        'messages' => [['id' => (string) Str::uuid(), 'role' => 'user', 'content' => $message]],
        'forwardedProps' => ['context' => [[
            'type' => 'craft:element',
            'ref' => 'editor-1',
            'open' => true,
            'elementType' => Asset::class,
            'elementId' => $asset->id,
            'siteId' => $asset->siteId,
            'namespace' => [],
        ]]],
    ]);
}

/**
 * Sends a message with an asset open in the editor, and returns the view_editor_image tool the model was offered, if
 * it was.
 */
function viewEditorImageTool(Asset $asset): ?ViewEditorImage
{
    AssistantAgent::fake(['Done.']);
    chatWithOpenAsset($asset, (string) Str::uuid(), 'Write alt text')->assertOk()->streamedContent();

    $tool = null;

    AssistantAgent::assertPrompted(function (AgentPrompt $prompt) use (&$tool): bool {
        foreach ($prompt->agent->tools() as $candidate) {
            if ($candidate instanceof ViewEditorImage) {
                $tool = $candidate;
            }
        }

        return $prompt->attachments->isEmpty();
    });

    return $tool;
}

it('shows a model with vision the image of the asset open in the editor when it asks, scaled down and kept by reference', function () {
    withVision(true);
    config()->set('filesystems.disks.temp-assets', ['driver' => 'local', 'root' => storage_path('framework/testing/temp-assets')]);
    Storage::fake('temp-assets');
    Cms::config()->tempAssetUploadDisk = 'temp-assets';

    $response = viewEditorImageTool($this->asset)->handle(new ToolRequest([]));
    $image = $response->attachments[0];

    expect($image)->toBeInstanceOf(StoredImage::class)
        ->and($image->disk)->toBe('temp-assets')
        ->and($image->name())->toBe('puck.jpg')
        ->and(array_slice(getimagesizefromstring(Storage::disk('temp-assets')->get($image->path)), 0, 2))->toBe([ModelImages::MAX_SIZE, ModelImages::MAX_SIZE / 2]);
    AssistantAgent::assertPrompted(fn (AgentPrompt $prompt): bool => str_contains($prompt->agent->instructions(), 'call view_editor_image'));
});

it('doesn’t offer images to models without vision', function () {
    withVision(false);

    expect(viewEditorImageTool($this->asset))->toBeNull();
    AssistantAgent::assertPrompted(fn (AgentPrompt $prompt): bool => ! str_contains($prompt->agent->instructions(), 'view_editor_image'));
});
