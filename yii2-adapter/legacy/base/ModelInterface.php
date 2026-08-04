<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use ArrayAccess;
use IteratorAggregate;
use yii\base\Arrayable;
use yii\base\StaticInstanceInterface;

/**
 * ModelInterface defines the common interface to be implemented by Craft model classes.
 *
 * A class implementing this interface should extend [[Model]].
 *
 * @mixin Model
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface ModelInterface extends StaticInstanceInterface, IteratorAggregate, ArrayAccess, Arrayable
{
}
