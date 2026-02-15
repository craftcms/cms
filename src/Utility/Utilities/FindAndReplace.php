<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use CraftCms\Cms\Cp\VueComponent;
use CraftCms\Cms\Utility\Utility;

use function CraftCms\Cms\t;

/**
 * FindAndReplace represents a FindAndReplace dashboard widget.
 */
final class FindAndReplace extends Utility
{
    #[\Override]
    public static function displayName(): string
    {
        return t('Find and Replace');
    }

    #[\Override]
    public static function id(): string
    {
        return 'find-replace';
    }

    #[\Override]
    public static function icon(): string
    {
        return 'wand-magic-sparkles';
    }

    #[\Override]
    public static function contentHtml(): string
    {
        return VueComponent::render('FindReplace');
    }
}
