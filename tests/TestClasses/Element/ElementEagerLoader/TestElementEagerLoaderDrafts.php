<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Element\ElementEagerLoader;

use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\User\Elements\User;

readonly class TestElementEagerLoaderDrafts extends Drafts
{
    #[\Override]
    public function withProvisionalDrafts(array $elements, ?User $user = null): array
    {
        TestElementEagerLoaderDraftsState::$calls++;

        return array_reverse($elements);
    }
}
