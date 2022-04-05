<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use craft\queue\BaseJob;

/**
 * DeleteStaleTemplateCaches job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 3.5.0
 */
class DeleteStaleTemplateCaches extends BaseJob
{
    /**
     * @var int|int[]|null The element ID(s) whose caches need to be cleared
     */
    public $elementId;

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        return;
    }
}
