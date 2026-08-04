<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Contracts;

use Stringable;

/**
 * Interface that designates a class's `__toString()` method as HTML-safe for Twig.
 */
interface SafeHtml extends Stringable {}
