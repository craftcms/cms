<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

/**
 * A nested-entry manager whose elements are managed outside the owner form.
 * Its cards and index metadata are presentation data, never a submitted field value.
 *
 * @deprecated Use {@see NestedElements}, which manages any nested element type.
 * @since 6.0.0
 */
class NestedEntries extends NestedElements {}
