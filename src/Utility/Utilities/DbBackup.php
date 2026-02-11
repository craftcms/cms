<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use CraftCms\Cms\Cp\VueComponent;
use CraftCms\Cms\Utility\Utility;

use function CraftCms\Cms\t;

/**
 * DbBackup represents a DbBackup dashboard widget.
 */
final class DbBackup extends Utility
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function displayName(): string
    {
        return t('Database Backup');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function id(): string
    {
        return 'db-backup';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function icon(): string
    {
        return 'database';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function contentHtml(): string
    {
        return VueComponent::render('DatabaseBackup');
    }
}
