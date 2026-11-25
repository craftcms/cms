<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\deletionblockers;

use craft\elements\ElementCollection;
use yii\base\BaseObject;

/**
 * BaseDeletionBlocker defines a base implementation for element deletion blockers.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.10.0
 */
abstract class BaseDeletionBlocker extends BaseObject implements DeletionBlockerInterface
{
    /**
     * Constructor
     */
    public function __construct(
        protected readonly ElementCollection $elements,
        protected readonly bool $hardDelete,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function getDetails(): ?string
    {
        return null;
    }
}
