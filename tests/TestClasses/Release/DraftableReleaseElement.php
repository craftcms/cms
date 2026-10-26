<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Release;

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\User\Elements\User;
use Override;

class DraftableReleaseElement extends Element
{
    #[Override]
    public static function hasTitles(): bool
    {
        return true;
    }

    #[Override]
    public static function hasDrafts(): bool
    {
        return true;
    }

    #[Override]
    public function canSave(User $user): bool
    {
        return $user->admin;
    }

    #[Override]
    public function canView(User $user): bool
    {
        return $user->admin;
    }
}
