<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Behavior;

use yii\base\Behavior;

class LegacyBehaviorState
{
    public bool $behaviorsLoaded = false;

    public bool $loadingBehaviors = false;

    /**
     * @var array<int|string, Behavior>
     */
    public array $behaviors = [];

    /**
     * @var array<string, array<int, array{0: callable, 1: mixed}>>
     */
    public array $events = [];

    public ?object $legacyOwner = null;

    public ?string $legacyOwnerClass = null;
}
