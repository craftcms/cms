<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * PreviewableFieldInterface defines the common interface to be implemented by field classes
 * that wish to be previewable in element table and card views.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.10.0
 * @mixin Field
 * @deprecated in 6.0.0. Use {@see \CraftCms\Cms\Field\Contracts\DefaultableFieldInterface} instead.
 */
interface DefaultableFieldInterface extends \CraftCms\Cms\Field\Contracts\DefaultableFieldInterface
{
}
