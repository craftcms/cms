<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\User\Models\User;

final class AuthorizeCreateDrafts
{
    public function __construct(
        public ElementInterface $element,
        public User $user,
        public bool $authorized = false,
    ) {}
}
