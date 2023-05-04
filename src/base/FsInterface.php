<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * FsInterface defines the common interface to be implemented by filesystem classes.
 * A class implementing this interface should also use [[SavableComponentTrait]] and [[FsTrait]].
 *
 * @mixin Fs
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface FsInterface extends BaseFsInterface, SavableComponentInterface
{
}
