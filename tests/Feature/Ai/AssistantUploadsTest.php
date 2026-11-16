<?php

declare(strict_types=1);

use CraftCms\Cms\Ai\Assistant\AssistantAgent;
use CraftCms\Cms\Ai\Assistant\Uploads\AssistantUploads;
use CraftCms\Cms\Ai\Assistant\Uploads\ViewUploads;
use CraftCms\Cms\Ai\CapabilityChecks;
use CraftCms\Cms\Ai\Data\CapabilityCheck;
use CraftCms\Cms\Ai\Data\ResolvedProvider;
use CraftCms\Cms\Ai\Enums\Capability;
use CraftCms\Cms\Ai\Http\Controllers\AssistantController;
use CraftCms\Cms\Ai\ModelImages;
use CraftCms\Cms\Ai\Models\AgentUpload;
use CraftCms\Cms\Ai\Settings;
use CraftCms\Cms\Ai\Tasks\Assistant;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Support\Facades\Assets;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Ai\Files\StoredDocument;
use Laravel\Ai\Files\StoredImage;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Tools\Request as ToolRequest;
use Laravel\Ai\Tools\ToolResponse;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    config(['ai.providers' => ['anthropic' => ['driver' => 'anthropic', 'key' => 'sk-test']]]);
    app()->instance(Settings::class, new Settings([
        'defaults' => ['text' => ['provider' => 'anthropic']],
        // Uploaded images would otherwise be analyzed by the model.
        'tasks' => array_fill_keys(['craft:title', 'craft:imageType', 'craft:focalPoint', 'craft:altText', 'craft:tagImage'], ['enabled' => false]),
    ]));
    Edition::set(Edition::Pro);

    config()->set('filesystems.disks.upload-parts', ['driver' => 'local', 'root' => storage_path('framework/testing/upload-parts')]);
    config()->set('filesystems.disks.temp-assets', ['driver' => 'local', 'root' => storage_path('framework/testing/temp-assets')]);
    Storage::fake('upload-parts');
    Storage::fake('temp-assets');
    Cms::config()->tempAssetUploadDisk = 'temp-assets';
    Cms::config()->uploadSessionDisk = 'upload-parts';

    $this->user = UserModel::factory()->withPermissions(['accessCp', Assistant::PERMISSION])->createElement();
    actingAs($this->user);
    withModelInputs(vision: true, documents: true);
});

/** Signs in as another user who can use the assistant. */
function actAsAnotherAssistantUser(): void
{
    session()->flush();
    actingAs(UserModel::factory()->withPermissions(['accessCp', Assistant::PERMISSION])->createElement());
}

/** Says which files the assistant’s model can read, as the AI settings checked it. */
function withModelInputs(bool $vision, bool $documents): void
{
    app()->instance(CapabilityChecks::class, new class($vision, $documents) extends CapabilityChecks
    {
        public function __construct(private readonly bool $vision, private readonly bool $documents) {}

        public function result(Capability $capability, ResolvedProvider $resolved): CapabilityCheck
        {
            return new CapabilityCheck($capability, $resolved->provider, 'model', match ($capability) {
                Capability::Vision => $this->vision,
                Capability::DocumentInput => $this->documents,
                default => true,
            });
        }
    });
}

/**
 * Uploads a file to a conversation, as the chat does, and returns the completed upload.
 *
 * @return array<string, mixed>
 */
function uploadToConversation(string $threadId, string $filename, string $contents): array
{
    $session = startConversationUpload($threadId, $filename, $contents)->assertCreated()->json();

    test()->call('PATCH', $session['transport']['options']['url'], server: [
        'CONTENT_TYPE' => 'application/offset+octet-stream',
        'HTTP_TUS_RESUMABLE' => '1.0.0', 'HTTP_UPLOAD_OFFSET' => 0,
    ], content: $contents)->assertNoContent();

    return postJson($session['urls']['complete'])->assertOk()->json();
}

function startConversationUpload(string $threadId, string $filename, string $contents): TestResponse
{
    return postJson(action([AssistantController::class, 'upload']), [
        'threadId' => $threadId,
        'filename' => $filename,
        'size' => strlen($contents),
    ]);
}

/**
 * Sends a message with uploaded files, as the chat does.
 *
 * @param  list<int>  $assetIds
 */
function chatWithUploads(string $threadId, string $message, array $assetIds): TestResponse
{
    return postJson(action([AssistantController::class, 'run']), [
        'threadId' => $threadId,
        'runId' => (string) Str::uuid(),
        'messages' => [['id' => (string) Str::uuid(), 'role' => 'user', 'content' => $message]],
        'forwardedProps' => ['context' => array_map(static fn (int $assetId): array => [
            'type' => 'craft:upload',
            'ref' => "upload-$assetId",
            'assetId' => $assetId,
        ], $assetIds)],
    ]);
}

function jpeg(int $width, int $height): string
{
    ob_start();
    imagejpeg(imagecreatetruecolor($width, $height));

    return (string) ob_get_clean();
}

/**
 * Sends a message with uploaded files, and returns the view_uploads tool the model was offered, if it was.
 *
 * @param  list<int>  $assetIds
 */
function viewUploadsTool(string $threadId, array $assetIds): ?ViewUploads
{
    AssistantAgent::fake(['Done.']);
    chatWithUploads($threadId, 'What’s in these?', $assetIds)->assertOk()->streamedContent();

    $tool = null;

    AssistantAgent::assertPrompted(function (AgentPrompt $prompt) use (&$tool): bool {
        foreach ($prompt->agent->tools() as $candidate) {
            if ($candidate instanceof ViewUploads) {
                $tool = $candidate;
            }
        }

        return true;
    });

    return $tool;
}

it('uploads files to the user’s temporary uploads folder, for the conversation', function () {
    $threadId = (string) Str::uuid();

    $upload = uploadToConversation($threadId, 'notes.txt', 'Q3 went well.');
    $asset = Asset::find()->id($upload['assetId'])->one();

    expect($asset->folderId)->toBe(Assets::getUserTemporaryUploadFolder($this->user)->id)
        ->and($upload['contextItem'])->toBe([
            'type' => 'craft:upload',
            'ref' => (string) $asset->id,
            'label' => 'notes.txt',
            'icon' => 'file-lines',
            'data' => ['assetId' => $asset->id],
        ])
        ->and(AgentUpload::query()->where('conversationId', $threadId)->pluck('assetId')->all())->toBe([$asset->id]);
});

it('won’t upload files to another user’s conversation', function () {
    AssistantAgent::fake(['Hello.']);
    $threadId = (string) Str::uuid();
    chatWithUploads($threadId, 'Hi', [])->assertOk()->streamedContent();

    actAsAnotherAssistantUser();

    startConversationUpload($threadId, 'notes.txt', 'Q3 went well.')->assertNotFound();
});

it('tells the model about the files uploaded to the conversation, and offers to show it what’s in them', function () {
    $threadId = (string) Str::uuid();
    $upload = uploadToConversation($threadId, 'notes.txt', 'Q3 went well.');

    AssistantAgent::fake(['Done.']);
    chatWithUploads($threadId, 'Summarize this', [$upload['assetId']])->assertOk()->streamedContent();

    AssistantAgent::assertPrompted(fn (AgentPrompt $prompt): bool => str_contains($prompt->agent->instructions(), "“notes.txt” (asset ID {$upload['assetId']}, text")
        && $prompt->attachments->isEmpty());
});

it('leaves out files that weren’t uploaded to the conversation by the user', function () {
    $other = uploadToConversation((string) Str::uuid(), 'other.txt', 'Elsewhere.');

    actAsAnotherAssistantUser();
    $threadId = (string) Str::uuid();

    expect(viewUploadsTool($threadId, [$other['assetId']]))->toBeNull();
    AssistantAgent::assertPrompted(fn (AgentPrompt $prompt): bool => ! str_contains($prompt->agent->instructions(), 'other.txt'));
});

it('shows the model images and PDFs as copies on the temporary asset upload disk, and text files as text', function () {
    $threadId = (string) Str::uuid();
    $image = uploadToConversation($threadId, 'chart.jpg', jpeg(3000, 1500));
    $pdf = uploadToConversation($threadId, 'report.pdf', '%PDF-1.4 sales');
    $text = uploadToConversation($threadId, 'notes.txt', 'Q3 went well.');
    $archive = uploadToConversation($threadId, 'archive.zip', "PK\x03\x04");

    $response = viewUploadsTool($threadId, [$image['assetId'], $pdf['assetId'], $text['assetId'], $archive['assetId']])
        ->handle(new ToolRequest(['assetIds' => [$image['assetId'], $pdf['assetId'], $text['assetId'], $archive['assetId']]]));

    [$attachedImage, $attachedPdf] = $response->attachments;

    expect($response)->toBeInstanceOf(ToolResponse::class)
        ->and($response->attachments)->toHaveCount(2)
        ->and($attachedImage)->toBeInstanceOf(StoredImage::class)
        ->and($attachedImage->disk)->toBe('temp-assets')
        ->and($attachedImage->name())->toBe('chart.jpg')
        ->and(array_slice(getimagesizefromstring(Storage::disk('temp-assets')->get($attachedImage->path)), 0, 2))->toBe([ModelImages::MAX_SIZE, ModelImages::MAX_SIZE / 2])
        ->and($attachedPdf)->toBeInstanceOf(StoredDocument::class)
        ->and(Storage::disk('temp-assets')->get($attachedPdf->path))->toBe('%PDF-1.4 sales')
        ->and($response->text)->toContain("<file name=\"notes.txt\">\nQ3 went well.\n</file>")
        ->and($response->text)->toContain("“archive.zip” (asset ID {$archive['assetId']}) can’t be shown to you");
});

it('names an image the model is shown for the format it was converted to', function () {
    $threadId = (string) Str::uuid();
    ob_start();
    imagegif(imagecreatetruecolor(10, 10));
    $gif = uploadToConversation($threadId, 'spinner.gif', (string) ob_get_clean());

    $response = viewUploadsTool($threadId, [$gif['assetId']])
        ->handle(new ToolRequest(['assetIds' => [$gif['assetId']]]));

    expect($response->attachments[0]->name())->toBe('spinner.png')
        ->and($response->attachments[0]->path)->toEndWith('.png');
});

it('only shows the model images and PDFs it can read', function () {
    withModelInputs(vision: false, documents: false);
    $threadId = (string) Str::uuid();
    $image = uploadToConversation($threadId, 'chart.jpg', jpeg(10, 10));
    $pdf = uploadToConversation($threadId, 'report.pdf', '%PDF-1.4 sales');

    $response = viewUploadsTool($threadId, [$image['assetId'], $pdf['assetId']])
        ->handle(new ToolRequest(['assetIds' => [$image['assetId'], $pdf['assetId']]]));

    expect($response->attachments)->toBe([])
        ->and($response->text)->toContain('“chart.jpg”')
        ->and($response->text)->toContain('can’t be shown to you');
});

it('gives the chat thumbnails of the images uploaded to the user’s own conversations', function () {
    $threadId = (string) Str::uuid();
    $image = uploadToConversation($threadId, 'chart.jpg', jpeg(100, 50));
    $text = uploadToConversation($threadId, 'notes.txt', 'Q3 went well.');
    AssistantAgent::fake(['Done.']);
    chatWithUploads($threadId, 'Look', [$image['assetId'], $text['assetId']])->assertOk()->streamedContent();

    $thumbnails = getJson(action([AssistantController::class, 'uploadThumbnails'], ['threadId' => $threadId]))
        ->assertOk()
        ->json('thumbnails');

    expect(array_keys($thumbnails))->toBe([$image['assetId']]);
    expect($image['thumbnail'])->toContain('generate-transform')
        ->and($text['thumbnail'])->toBeNull();

    actAsAnotherAssistantUser();

    getJson(action([AssistantController::class, 'uploadThumbnails'], ['threadId' => $threadId]))->assertNotFound();
});

it('deletes a file the user removed before sending it', function () {
    $threadId = (string) Str::uuid();
    $upload = uploadToConversation($threadId, 'notes.txt', 'Q3 went well.');

    postJson(action([AssistantController::class, 'removeUpload']), ['threadId' => $threadId, 'assetId' => $upload['assetId']])->assertOk();

    expect(Asset::find()->id($upload['assetId'])->status(null)->exists())->toBeFalse()
        ->and(AgentUpload::query()->count())->toBe(0);
});

it('deletes a conversation’s uploads with it, keeping those moved into a volume, related to something, or used as a user’s photo', function () {
    $threadId = (string) Str::uuid();
    $temporary = uploadToConversation($threadId, 'temporary.txt', 'Temporary.');
    $moved = uploadToConversation($threadId, 'moved.txt', 'Moved.');
    $used = uploadToConversation($threadId, 'used.txt', 'Used.');
    $photo = uploadToConversation($threadId, 'photo.jpg', jpeg(10, 10));
    AssistantAgent::fake(['Done.']);
    chatWithUploads($threadId, 'Save these', [$temporary['assetId'], $moved['assetId'], $used['assetId']])->assertOk()->streamedContent();
    Storage::disk('temp-assets')->put("ai-assistant/$threadId/copy.png", 'copy');

    $volume = Volume::factory()->create();
    $folder = VolumeFolder::factory()->create(['volumeId' => $volume->id, 'path' => '']);
    DB::table(Table::ASSETS)->where('id', $moved['assetId'])->update(['volumeId' => $volume->id, 'folderId' => $folder->id]);
    DB::table(Table::RELATIONS)->insert([
        'fieldId' => Field::factory()->create()->id,
        'sourceId' => $this->user->id,
        'targetId' => $used['assetId'],
        'sortOrder' => 1,
        'dateCreated' => Query::prepareDateForDb(now()),
        'dateUpdated' => Query::prepareDateForDb(now()),
        'uid' => (string) Str::uuid(),
    ]);
    DB::table(Table::USERS)->where('id', $this->user->id)->update(['photoId' => $photo['assetId']]);

    postJson(action([AssistantController::class, 'destroy']), ['threadId' => $threadId])->assertOk();

    expect(Asset::find()->id($temporary['assetId'])->status(null)->exists())->toBeFalse()
        ->and(Asset::find()->id($moved['assetId'])->status(null)->exists())->toBeTrue()
        ->and(Asset::find()->id($used['assetId'])->status(null)->exists())->toBeTrue()
        ->and(Asset::find()->id($photo['assetId'])->status(null)->exists())->toBeTrue()
        ->and(AgentUpload::query()->count())->toBe(0);
    Storage::disk('temp-assets')->assertMissing("ai-assistant/$threadId/copy.png");
});

it('still deletes a user’s uploads with their conversations once the user is deleted', function () {
    $threadId = (string) Str::uuid();
    $upload = uploadToConversation($threadId, 'notes.txt', 'Q3 went well.');
    AssistantAgent::fake(['Done.']);
    chatWithUploads($threadId, 'Summarize this', [$upload['assetId']])->assertOk()->streamedContent();

    Elements::deleteElement($this->user, true);
    app(AssistantUploads::class)->deleteForConversations([$threadId]);

    expect(Asset::find()->id($upload['assetId'])->status(null)->exists())->toBeFalse()
        ->and(AgentUpload::query()->count())->toBe(0);
});

it('deletes files uploaded to conversations that were never sent, a day later', function () {
    $abandoned = uploadToConversation((string) Str::uuid(), 'abandoned.txt', 'Abandoned.');
    $recent = uploadToConversation((string) Str::uuid(), 'recent.txt', 'Recent.');
    AgentUpload::query()->where('assetId', $abandoned['assetId'])->update([
        'dateCreated' => Query::prepareDateForDb(now()->subHours(AssistantUploads::ABANDONED_AFTER_HOURS + 1)),
    ]);

    app(AssistantUploads::class)->deleteAbandoned();

    expect(Asset::find()->id($abandoned['assetId'])->status(null)->exists())->toBeFalse()
        ->and(Asset::find()->id($recent['assetId'])->status(null)->exists())->toBeTrue();
});
