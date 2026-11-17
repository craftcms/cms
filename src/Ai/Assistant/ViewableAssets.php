<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant;

use CraftCms\Cms\Ai\Assistant\Uploads\AssistantUploads;
use CraftCms\Cms\Ai\Enums\Capability;
use CraftCms\Cms\Ai\ModelImages;
use CraftCms\Cms\Ai\Tasks\Assistant;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\FileKind;
use CraftCms\Cms\Support\Facades\Ai;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Str;
use Illuminate\Container\Attributes\Singleton;
use Laravel\Ai\Files\Document;
use Laravel\Ai\Files\Image;

/**
 * Prepares assets for the AI assistant’s model to look at, such as files the user uploaded or the image open in their
 * editor: images and PDFs as files, if its model can see them, and text as text.
 *
 * Images and PDFs are copied to {@see AssistantUploads::disk()}, so the conversation stores where they are rather than
 * what’s in them, and can show them to the model again. Images are scaled down first. The copies are deleted with the
 * conversation.
 *
 * @internal
 *
 * @since 6.0.0
 */
#[Singleton]
class ViewableAssets
{
    /** The largest PDF the model is sent, in bytes. */
    public const int MAX_PDF_SIZE = 20 * 1024 * 1024;

    /** The most characters of a text file the model is sent. */
    public const int MAX_TEXT_LENGTH = 100_000;

    public function __construct(
        private readonly AssistantUploads $uploads,
        private readonly ModelImages $modelImages,
    ) {}

    /**
     * Returns whether the model can look at what’s in an asset’s file.
     */
    public function canView(Asset $asset): bool
    {
        return match (true) {
            $this->isText($asset) => true,
            $asset->kind === FileKind::Image->value => Ai::supports(Assistant::handle(), Capability::Vision),
            $asset->kind === FileKind::Pdf->value => ($asset->size ?? 0) <= self::MAX_PDF_SIZE
                && Ai::supports(Assistant::handle(), Capability::DocumentInput),
            default => false,
        };
    }

    /**
     * Returns an asset’s file as the model looks at it: its text, or a file kept by reference. Returns null if the
     * model can’t look at it.
     */
    public function view(string $conversationId, Asset $asset): string|Image|Document|null
    {
        if (! $this->canView($asset)) {
            return null;
        }

        if ($this->isText($asset)) {
            return $this->text($asset);
        }

        $isImage = $asset->kind === FileKind::Image->value;
        $copy = $this->copy($conversationId, $asset, $isImage);

        return $isImage
            // Named for the format it was converted to, such as a HEIC sent as a PNG.
            ? Image::fromStorage($copy, $this->uploads->diskName())->as(
                $asset->getFilename(false).'.'.pathinfo($copy, PATHINFO_EXTENSION),
            )
            : Document::fromStorage($copy, $this->uploads->diskName())->as($asset->getFilename());
    }

    private function isText(Asset $asset): bool
    {
        $kind = FileKind::tryFrom((string) $asset->kind);

        return $kind?->isText() === true
            || $kind === FileKind::CaptionsSubtitles
            || in_array($asset->getMimeType(), ['text/csv', 'text/tab-separated-values'], true);
    }

    private function text(Asset $asset): ?string
    {
        $contents = $asset->getContents();

        if (! mb_check_encoding($contents, 'UTF-8')) {
            return null;
        }

        return Str::limit($contents, self::MAX_TEXT_LENGTH, "\n\n[The rest of the file was left out.]");
    }

    /**
     * Copies an asset’s file to the disk, as the model gets it, unless it’s already there, and returns its path there.
     * The path changes when the file does.
     */
    private function copy(string $conversationId, Asset $asset, bool $isImage): string
    {
        $base = "{$this->uploads->copiesPath($conversationId)}/{$asset->id}-{$asset->dateModified?->getTimestamp()}";
        $disk = $this->uploads->disk();

        foreach ($isImage ? ['jpg', 'png'] : ['pdf'] as $extension) {
            if ($disk->exists("$base.$extension")) {
                return "$base.$extension";
            }
        }

        if (! $isImage) {
            $disk->put("$base.pdf", $asset->getContents());

            return "$base.pdf";
        }

        $local = $asset->getCopyOfFile();

        try {
            $image = $this->modelImages->forFile($local);
        } finally {
            File::delete($local);
        }

        $path = "$base.".($image->mimeType() === 'image/jpeg' ? 'jpg' : 'png');
        $disk->put($path, base64_decode($image->base64));

        return $path;
    }
}
