<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\tasks;

use Craft;
use craft\base\Task;

/**
 * GeneratePendingTransforms represents a Generate Pending Transforms background task.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class GeneratePendingTransforms extends Task
{
    // Properties
    // =========================================================================

    /**
     * @var int[]|null The pending transform index IDs
     */
    private $_indexIds;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTotalSteps(): int
    {
        // Get all of the pending transform index IDs
        $this->_indexIds = Craft::$app->getAssetTransforms()->getPendingTransformIndexIds();

        return count($this->_indexIds);
    }

    /**
     * @inheritdoc
     */
    public function runStep(int $step)
    {
        // Don't let an exception stop us from processing the rest
        try {
            $index = Craft::$app->getAssetTransforms()->getTransformIndexModelById($this->_indexIds[$step]);

            if (!$index) {
                // No transform means a probably already finished transform
                return true;
            }

            Craft::$app->getAssetTransforms()->ensureTransformUrlByIndexModel($index);
        } catch (\Throwable $e) {
            // Swallow it.
        }

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('app', 'Generating pending image transforms');
    }
}
