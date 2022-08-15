<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\base\ElementInterface;
use craft\i18n\Translation;
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
     * @var string The type of elements to update.
     * @phpstan-var class-string<ElementInterface>
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
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            if (!$generalConfig->maxRevisions) {
                return;
            }
            $this->maxRevisions = $generalConfig->maxRevisions;
        }

        /** @var string|ElementInterface $class */
        /** @phpstan-var class-string<ElementInterface>|ElementInterface $class */
        $class = $this->elementType;
        $extraRevisions = $class::find()
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
        return Translation::prep('app', 'Pruning extra revisions');
    }
}
