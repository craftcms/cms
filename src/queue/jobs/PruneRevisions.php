<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\base\ElementInterface;
use craft\queue\BaseJob;

/**
 * PruneRevisions job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class PruneRevisions extends BaseJob
{
    /**
     * @var string|ElementInterface The type of elements to update.
     */
    public $elementType;

    /**
     * @var int The ID of the source element.
     */
    public $sourceId;

    /**
     * @var int The site ID of the source element
     */
    public $siteId;

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        // Make sure maxRevisions is still set
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if (!$generalConfig->maxRevisions) {
            return;
        }

        $class = $this->elementType;
        $extraRevisions = $class::find()
            ->revisionOf($this->sourceId)
            ->siteId($this->siteId)
            ->anyStatus()
            ->orderBy(['num' => SORT_DESC])
            ->offset($generalConfig->maxRevisions)
            ->all();

        if (empty($extraRevisions)) {
            return;
        }

        $total = count($extraRevisions);
        $elementsService = Craft::$app->getElements();

        foreach ($extraRevisions as $i => $extraRevision) {
            $this->setProgress($queue, ($i + 1) / $total);
            $elementsService->deleteElement($extraRevision, true);
        }
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('app', 'Pruning extra revisions');
    }
}
