<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Attributes;

use Attribute;

/**
 * Marks classes/properties/methods as allowed in Twig sandbox.
 *
 * @since 5.9.0
 */
#[Attribute]
final class AllowedInSandbox {}
