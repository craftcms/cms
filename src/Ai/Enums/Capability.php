<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Enums;

use function CraftCms\Cms\t;

/**
 * Something a task needs from its model, beyond its {@see Modality}.
 *
 * @since 6.0.0
 */
enum Capability: string
{
    /** The model can read images attached to text prompts. */
    case Vision = 'vision';

    /** The model can read documents, such as PDFs, attached to text prompts. */
    case DocumentInput = 'documentInput';

    /** The model can hear audio attached to text prompts. */
    case AudioInput = 'audioInput';

    /** The model can watch videos attached to text prompts. */
    case VideoInput = 'videoInput';

    /** The model can return output that follows a JSON schema, for agents with structured output. */
    case StructuredOutput = 'structuredOutput';

    /** The model can call tools, for agents with tools. */
    case ToolCalling = 'toolCalling';

    public function label(): string
    {
        return match ($this) {
            self::Vision => t('Vision'),
            self::DocumentInput => t('Document input'),
            self::AudioInput => t('Audio input'),
            self::VideoInput => t('Video input'),
            self::StructuredOutput => t('Structured output'),
            self::ToolCalling => t('Tool calling'),
        };
    }
}
