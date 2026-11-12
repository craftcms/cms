<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Element;

use CraftCms\Cms\Element\Element;
use Override;

class TestRevisionableElement extends Element
{
    #[Override]
    public static function hasTitles(): bool
    {
        return true;
    }

    #[Override]
    public static function supportsRevisions(): bool
    {
        return true;
    }

    #[Override]
    public function hasRevisions(): bool
    {
        return true;
    }
}
