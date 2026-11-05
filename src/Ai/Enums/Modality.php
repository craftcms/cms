<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Enums;

use Laravel\Ai\Contracts\Providers\AudioProvider;
use Laravel\Ai\Contracts\Providers\ClassificationProvider;
use Laravel\Ai\Contracts\Providers\EmbeddingProvider;
use Laravel\Ai\Contracts\Providers\ImageProvider;
use Laravel\Ai\Contracts\Providers\RerankingProvider;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Contracts\Providers\TranscriptionProvider;

use function CraftCms\Cms\t;

/**
 * The kind of AI operation a task performs.
 *
 * @since 6.0.0
 */
enum Modality: string
{
    case Text = 'text';
    case Image = 'image';
    case Audio = 'audio';
    case Transcription = 'transcription';
    case Embeddings = 'embeddings';
    case Reranking = 'reranking';
    case Classification = 'classification';

    public function label(): string
    {
        return match ($this) {
            self::Text => t('Text'),
            self::Image => t('Image'),
            self::Audio => t('Audio'),
            self::Transcription => t('Transcription'),
            self::Embeddings => t('Embeddings'),
            self::Reranking => t('Reranking'),
            self::Classification => t('Classification'),
        };
    }

    /**
     * Returns the `laravel/ai` provider contract that supports this modality.
     *
     * @return class-string
     */
    public function providerContract(): string
    {
        return match ($this) {
            self::Text => TextProvider::class,
            self::Image => ImageProvider::class,
            self::Audio => AudioProvider::class,
            self::Transcription => TranscriptionProvider::class,
            self::Embeddings => EmbeddingProvider::class,
            self::Reranking => RerankingProvider::class,
            self::Classification => ClassificationProvider::class,
        };
    }
}
