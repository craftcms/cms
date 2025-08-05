<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use CraftCms\Cms\Support\Contracts\ValidatableInterface;

/**
 * WidgetInterface defines the common interface to be implemented by dashboard widget classes.
 * A class implementing this interface should also use [[SavableComponentTrait]] and [[WidgetTrait]].
 *
 * @mixin WidgetTrait
 * @mixin SavableComponentTrait
 * @mixin Model
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 6.0.0. Use `\CraftCms\Cms\Dashboard\Contracts\WidgetInterface` instead.
 */
interface WidgetInterface extends \CraftCms\Cms\Dashboard\Contracts\WidgetInterface, ValidatableInterface
{
}
