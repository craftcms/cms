<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Attributes;

use Attribute;

/**
 * Marks classes/properties/methods as allowed in Twig sandbox.
 *
 * @since 6.0.0
 */
#[Attribute]
class AllowedInSandbox {}
