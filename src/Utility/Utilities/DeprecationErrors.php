<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use CraftCms\Cms\Cp\VueComponent;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Utility\Utility;

use function CraftCms\Cms\t;

/**
 * DeprecationErrors represents a DeprecationErrors dashboard widget.
 */
final class DeprecationErrors extends Utility
{
    #[\Override]
    public static function displayName(): string
    {
        return t('Deprecation Warnings');
    }

    #[\Override]
    public static function id(): string
    {
        return 'deprecation-errors';
    }

    #[\Override]
    public static function icon(): string
    {
        return 'bug';
    }

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
        return VueComponent::render('DeprecationErrors', [
            ':logs' => Deprecator::getLogs(),
        ]);
    }
}
