<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Enums;

/**
 * Why a task can't resolve a provider.
 *
 * @since 6.0.0
 */
enum AiUnavailableReason: string
{
    case UnknownTask = 'unknownTask';
    case TaskDisabled = 'taskDisabled';
    case NoDefault = 'noDefault';
    case UnknownProvider = 'unknownProvider';
    case MissingCapability = 'missingCapability';
    case Canceled = 'canceled';
}
