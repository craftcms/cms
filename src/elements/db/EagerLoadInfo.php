<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

use craft\base\ElementInterface;

/**
 * Class EagerLoadInfo
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class EagerLoadInfo
{
    /**
     * @param EagerLoadPlan $plan The eager loading plan
     * @param ElementInterface[] $sourceElements The source elements
     */
    public function __construct(
        public EagerLoadPlan $plan,
        public array $sourceElements,
    ) {
    }
}
