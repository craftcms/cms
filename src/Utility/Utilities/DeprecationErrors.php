<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Utility\Utility;

use function CraftCms\Cms\t;

/**
 * DeprecationErrors represents a DeprecationErrors dashboard widget.
 */
final class DeprecationErrors extends Utility
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function displayName(): string
    {
        return t('Deprecation Warnings');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function id(): string
    {
        return 'deprecation-errors';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function icon(): string
    {
        return 'bug';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function badgeCount(): int
    {
        return Deprecator::getTotalLogs();
    }

    #[\Override]
    public static function toolbarHtml(): string
    {
        return view('c::utilities.deprecation-errors.toolbar', [
            'logs' => Deprecator::getLogs(),
        ])->toHtml();
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function contentHtml(): string
    {
        return view('c::utilities.deprecation-errors.content', [
            'logs' => Deprecator::getLogs(),
        ])->toHtml();
    }
}
