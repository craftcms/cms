<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Contracts;

use CraftCms\Cms\Component\Contracts\Grippable;
use CraftCms\Cms\FieldLayout\FieldLayout;

/**
 * FieldLayoutProviderInterface defines the common interface to be implemented by classes
 * which provide a field layout.
 */
interface FieldLayoutProviderInterface extends Grippable
{
    public function getFieldLayout(): FieldLayout;
}
