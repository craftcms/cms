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
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Support\Facades\I18N;

/**
 * PruneRevisions job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class PruneRevisions extends BaseJob
{
    /**
     * @var class-string<ElementInterface> The type of elements to update.
     */
    public string $elementType;

    /**
     * @var int The ID of the canonical element.
     */
    public int $canonicalId;

    /**
     * @var int The site ID of the source element
     */
    public int $siteId;

    /**
     * @var int|null The maximum number of revisions an element can have
     * @since 3.5.13
     */
    public ?int $maxRevisions = null;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        if (!$this->maxRevisions) {
            // Make sure maxRevisions is still set
            $generalConfig = app(GeneralConfig::class);
            if (!$generalConfig->maxRevisions) {
                return;
            }
            $this->maxRevisions = $generalConfig->maxRevisions;
        }

        $extraRevisions = $this->elementType::find()
            ->revisionOf($this->canonicalId)
            ->siteId($this->siteId)
            ->status(null)
            ->orderBy(['num' => SORT_DESC])
            ->offset($this->maxRevisions)
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
    protected function defaultDescription(): ?string
    {
        return I18N::prep('Pruning extra revisions');
    }
}
