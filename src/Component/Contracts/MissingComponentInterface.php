<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Contracts;

use CraftCms\Cms\Component\Concerns\MissingComponentTrait;

/**
 * MissingComponentInterface defines the common interface for classes that represent a missing component class.
 * A class implementing this interface should also implement [[ComponentInterface]] and have a `toArray` method.
 * and use [[MissingComponentTrait]].
 *
 * @mixin MissingComponentTrait @phpstan-ignore mixin.trait
 */
interface MissingComponentInterface extends ComponentInterface {}
