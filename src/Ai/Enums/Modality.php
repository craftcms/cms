<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Enums;

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
}
