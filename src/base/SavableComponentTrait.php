<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use DateTime;

/**
 * SavableComponentTrait implements the common methods and properties for savable component classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
trait SavableComponentTrait
{
    /**
     * @var int|string|null The component’s ID (could be a temporary one: "new:X")
     */
    public int|string|null $id = null;

    /**
     * @var DateTime|null The date that the component was created
     */
    public ?DateTime $dateCreated = null;

    /**
     * @var DateTime|null The date that the component was last updated
     */
    public ?DateTime $dateUpdated = null;
}
