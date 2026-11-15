<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant\Uploads;

use CraftCms\Cms\Ai\Models\AgentUpload;
use CraftCms\Cms\Asset\Assets;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\FileKind;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Files users upload to AI assistant conversations. They’re saved as assets in the uploader’s temporary uploads folder,
 * and deleted with the conversation, unless they’ve been moved out of that folder or used somewhere by then.
 *
 * The copies of them that the assistant’s model has looked at are kept on the temporary asset upload disk, so the
 * conversation can show them to the model again without storing their contents in its messages.
 *
 * @internal
 *
 * @since 6.0.0
 */
#[Singleton]
class AssistantUploads
{
    /** How long a file can wait to be sent, before it’s deleted with the conversation it was uploaded to never stored. */
    public const int ABANDONED_AFTER_HOURS = 24;

    /** The size of uploaded images’ thumbnails, in pixels, for chips at twice their size. */
    public const int THUMBNAIL_SIZE = 48;

    public function __construct(
        private readonly Elements $elements,
        private readonly Assets $assets,
    ) {}

    /**
     * Records that a user uploaded an asset to a conversation.
     */
    public function record(string $conversationId, Asset $asset, User $user): void
    {
        $upload = new AgentUpload;
        $upload->conversationId = $conversationId;
        $upload->assetId = $asset->id;
        $upload->userId = $user->id;
        $upload->save();
    }

    /**
     * Returns the assets with the given IDs that a user uploaded to a conversation, keyed by their IDs. Those that have
     * since been moved out of the user’s temporary uploads folder are included too.
     *
     * @param  list<int>  $assetIds
     * @return array<int, Asset>
     */
    public function assets(string $conversationId, User $user, array $assetIds): array
    {
        $ids = AgentUpload::query()
            ->where('conversationId', $conversationId)
            ->where('userId', $user->id)
            ->whereIn('assetId', $assetIds)
            ->pluck('assetId')
            ->all();

        if ($ids === []) {
            return [];
        }

        $assets = [];

        foreach (Asset::find()->id($ids)->status(null)->all() as $asset) {
            $assets[$asset->id] = $asset;
        }

        return $assets;
    }

    /**
     * Returns thumbnails of the images a user uploaded to a conversation, keyed by their asset IDs, for the chat to show
     * on their chips. Images that have been deleted are left out.
     *
     * @return array<int, string>
     */
    public function thumbnails(string $conversationId, User $user): array
    {
        $assetIds = AgentUpload::query()
            ->where('conversationId', $conversationId)
            ->where('userId', $user->id)
            ->pluck('assetId')
            ->all();

        if ($assetIds === []) {
            return [];
        }

        $thumbnails = [];

        foreach (Asset::find()->id($assetIds)->kind(FileKind::Image->value)->status(null)->all() as $asset) {
            $url = $this->thumbnail($asset);

            if ($url !== null) {
                $thumbnails[$asset->id] = $url;
            }
        }

        return $thumbnails;
    }

    /**
     * Returns the thumbnail of an uploaded image, for its chip, or null if it isn’t an image.
     */
    public function thumbnail(Asset $asset): ?string
    {
        if ($asset->kind !== FileKind::Image->value) {
            return null;
        }

        return $this->assets->getThumbUrl($asset, self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE, iconFallback: false);
    }

    /**
     * Deletes a file a user uploaded to a conversation, if it’s still a temporary upload that isn’t used anywhere.
     */
    public function remove(string $conversationId, int $assetId, User $user): void
    {
        $upload = AgentUpload::query()
            ->where('conversationId', $conversationId)
            ->where('assetId', $assetId)
            ->where('userId', $user->id)
            ->first();

        if ($upload !== null) {
            $this->delete([$upload]);
        }
    }

    /**
     * Deletes the files uploaded to the given conversations, and the copies of them the model looked at. Those that
     * have been moved out of a temporary uploads folder or used somewhere are kept, but no longer belong to the
     * conversations.
     *
     * @param  iterable<string>  $conversationIds
     */
    public function deleteForConversations(iterable $conversationIds): void
    {
        $conversationIds = [...$conversationIds];

        if ($conversationIds === []) {
            return;
        }

        $this->delete(AgentUpload::query()->whereIn('conversationId', $conversationIds)->get()->all());

        foreach ($conversationIds as $conversationId) {
            $this->disk()->deleteDirectory($this->copiesPath($conversationId));
        }
    }

    /**
     * Deletes files that were uploaded to conversations which were never stored, as their messages were never sent.
     */
    public function deleteAbandoned(): void
    {
        $uploads = AgentUpload::query()
            ->where('dateCreated', '<', Query::prepareDateForDb(now()->subHours(self::ABANDONED_AFTER_HOURS)))
            ->whereNotExists(static fn ($query) => $query
                ->from(Table::AGENT_CONVERSATIONS)
                ->whereColumn(Table::AGENT_CONVERSATIONS.'.id', Table::AGENT_UPLOADS.'.conversationId'))
            ->get();

        $this->delete($uploads->all());

        foreach ($uploads->pluck('conversationId')->unique() as $conversationId) {
            $this->disk()->deleteDirectory($this->copiesPath($conversationId));
        }
    }

    /**
     * The disk that the copies of uploads the model looked at are kept on.
     */
    public function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    public function diskName(): string
    {
        return Cms::config()->getTempAssetUploadDisk();
    }

    /**
     * The directory that the copies of a conversation’s uploads are kept in, on {@see disk()}.
     */
    public function copiesPath(string $conversationId): string
    {
        return "ai-assistant/{$conversationId}";
    }

    /**
     * @param  list<AgentUpload>  $uploads
     */
    private function delete(array $uploads): void
    {
        if ($uploads === []) {
            return;
        }

        $assetIds = array_map(static fn (AgentUpload $upload): int => $upload->assetId, $uploads);

        $used = DB::table(Table::RELATIONS)
            ->whereIn('targetId', $assetIds)
            ->distinct()
            ->pluck('targetId')
            ->all();

        $unused = array_values(array_diff($assetIds, $used));
        $assets = $unused === [] ? [] : Asset::find()->id($unused)->status(null)->all();

        foreach ($assets as $asset) {
            if ($asset->getVolumeId() === null) {
                $this->elements->deleteElement($asset, true);
            }
        }

        AgentUpload::query()->whereKey(array_map(static fn (AgentUpload $upload): int => $upload->id, $uploads))->delete();
    }
}
