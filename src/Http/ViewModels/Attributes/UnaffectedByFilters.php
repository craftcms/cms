<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels\Attributes;

use Attribute;
use CraftCms\Cms\Http\ViewModels\ContentIndexViewModel;

/**
 * Marks an element index payload method that a search, status or filter change can't affect, such as one that depends
 * only on the source and site. The index leaves it out of the partial reloads that those changes make.
 *
 * The index reloads every unmarked method. A method that should have this attribute but doesn't costs some extra work,
 * but never shows stale data. An overriding method inherits the attribute from the method it overrides.
 *
 * @see ContentIndexViewModel::filterProps()
 * @since 6.0.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
class UnaffectedByFilters {}
