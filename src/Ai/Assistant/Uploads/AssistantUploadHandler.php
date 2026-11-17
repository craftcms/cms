<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant\Uploads;

use CraftCms\Cms\Ai\Tasks\Assistant;
use CraftCms\Cms\Asset\AssetUploads;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\FileKind;
use CraftCms\Cms\Filesystem\Contracts\UploadHandler;
use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\Support\Facades\Assets;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Contracts\VerifiesConversationOwnership;
use Laravel\Ai\Models\Conversation;

/**
 * Uploads files to an AI assistant conversation: as assets in the user’s temporary uploads folder, which belong to the
 * conversation.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class AssistantUploadHandler implements UploadHandler
{
    public function __construct(
        private AssetUploads $assetUploads,
        private AssistantUploads $uploads,
        private ConversationStore $conversations,
    ) {}

    public function authorize(Request $request, array $parameters, string $filename, int $size): array
    {
        $user = $this->user($request);
        $threadId = $parameters['threadId'] ?? null;

        abort_unless(is_string($threadId) && Str::isUuid($threadId), 422, 'A conversation must be given.');
        abort_unless($this->canUploadTo($threadId, $user), 404);

        return [
            ...$this->assetUploads->authorize($request, [
                'operation' => 'upload',
                'folderId' => Assets::getUserTemporaryUploadFolder($user)->id,
            ], $filename, $size),
            'threadId' => $threadId,
        ];
    }

    public function complete(Request $request, array $parameters, UploadedFile $file): JsonResponse
    {
        $user = $this->user($request);
        $threadId = $parameters['threadId'];
        unset($parameters['threadId']);

        $response = $this->assetUploads->complete($request, $parameters, $file);
        $data = $response->getData(true);
        $asset = is_int($data['assetId'] ?? null) && $response->isSuccessful()
            ? Asset::find()->id($data['assetId'])->status(null)->one()
            : null;

        if (! $asset instanceof Asset) {
            return $response;
        }

        $this->uploads->record($threadId, $asset, $user);

        return new JsonResponse([
            ...$data,
            'contextItem' => self::contextItem($asset),
            // The chat shows it in place of the browser’s preview of the file, which it can then let go of.
            'thumbnail' => $this->uploads->thumbnail($asset),
        ], $response->getStatusCode());
    }

    /**
     * Returns the context item that refers to an upload in a message.
     *
     * @return array{type: string, ref: string, label: string, icon: string, data: array{assetId: int}}
     */
    public static function contextItem(Asset $asset): array
    {
        return [
            'type' => UploadContextType::handle(),
            'ref' => (string) $asset->id,
            'label' => $asset->getFilename(),
            'icon' => self::icon(FileKind::tryFrom((string) $asset->kind)),
            'data' => ['assetId' => $asset->id],
        ];
    }

    private static function icon(?FileKind $kind): string
    {
        return match ($kind) {
            FileKind::Image => 'image',
            FileKind::Pdf => 'file-pdf',
            FileKind::Audio => 'file-audio',
            FileKind::Video => 'file-video',
            FileKind::Compressed => 'file-zipper',
            FileKind::Excel => 'file-excel',
            FileKind::Word => 'file-word',
            FileKind::Powerpoint => 'file-powerpoint',
            FileKind::Html, FileKind::Javascript, FileKind::Json, FileKind::Php, FileKind::Xml => 'file-code',
            FileKind::Markdown, FileKind::Text, FileKind::CaptionsSubtitles => 'file-lines',
            default => 'file',
        };
    }

    /**
     * Whether a user can upload files to a conversation: theirs, or one that hasn’t been stored yet, as a new
     * conversation is stored when its first message is sent.
     */
    private function canUploadTo(string $threadId, User $user): bool
    {
        if (! Conversation::query()->whereKey($threadId)->exists()) {
            return true;
        }

        assert($this->conversations instanceof VerifiesConversationOwnership);

        return $this->conversations->conversationBelongsTo(
            $threadId,
            Conversation::participantType($user),
            Conversation::participantKey($user),
        );
    }

    private function user(Request $request): User
    {
        $user = $request->craftUser() ?? abort(401);
        abort_unless($user->can(Assistant::PERMISSION), 403);

        return $user->asElement();
    }
}
