<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Dashboard;

use CraftCms\Cms\Dashboard\Widgets\Widget;
use Override;

use function CraftCms\Cms\currentUser;

/**
 * A widget that only admins can select, like plugin widgets that require a permission.
 */
class AdminOnlyWidget extends Widget
{
    public const string HTML = 'admin-only widget body';

    #[Override]
    public static function isSelectable(): bool
    {
        return currentUser()?->isAdmin() ?? false;
    }

    #[Override]
    public function component(): string
    {
        return 'craft:html-widget';
    }

    #[Override]
    public function props(): array
    {
        return ['html' => self::HTML];
    }
}
