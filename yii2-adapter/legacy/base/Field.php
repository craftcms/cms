<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use CraftCms\Cms\Field\Contracts\FieldInterface;

/** @phpstan-ignore-next-line */
if (false) {
    abstract class Field extends SavableComponent implements FieldInterface, Iconic, Actionable
    {
    }
}

class_alias(\CraftCms\Cms\Field\Field::class, Field::class);
