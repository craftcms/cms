<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Enums;

use function CraftCms\Cms\t;

/**
 * A model capability a task needs.
 *
 * Capabilities are shown to admins when they choose a model. Craft doesn't validate them.
 *
 * @since 6.0.0
 */
enum Capability: string
{
    case Vision = 'vision';
    case StructuredOutput = 'structuredOutput';
    case Tools = 'tools';

    public function label(): string
    {
        return match ($this) {
            self::Vision => t('Vision'),
            self::StructuredOutput => t('Structured output'),
            self::Tools => t('Tools'),
        };
    }
}
