<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\base\ElementInterface;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\queue\BaseJob;
use craft\queue\QueueInterface;
use yii\queue\Queue;

/**
 * UpdateElementSlugsAndUris job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UpdateElementSlugsAndUris extends BaseJob
{
    /**
     * @var int|int[]|null The ID(s) of the element(s) to update
     */
    public $elementId;

    /**
     * @var string|ElementInterface|null The type of elements to update.
     */
    public $elementType;

    /**
     * @var int|null The site ID of the elements to update.
     */
    public $siteId;

    /**
     * @var bool Whether the elements’ other sites should be updated as well.
     */
    public $updateOtherSites = true;

    /**
     * @var bool Whether the elements’ descendants should be updated as well.
     */
    public $updateDescendants = true;

    /**
     * @var int The total number of elements we are dealing with.
     */
    private $_totalToProcess;

    /**
     * @var int The number of elements we've dealt with so far
     */
    private $_totalProcessed;

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        $this->_totalToProcess = 0;
        $this->_totalProcessed = 0;

        $query = $this->_createElementQuery()
            ->id($this->elementId);

        $this->_processElements($queue, $query);
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('app', 'Updating element slugs and URIs');
    }

    /**
     * Creates an element query for the configured element type.
     *
     * @return ElementQueryInterface
     */
    private function _createElementQuery(): ElementQueryInterface
    {
        $class = $this->elementType;

        return $class::find()
            ->siteId($this->siteId)
            ->anyStatus();
    }

    /**
     * Updates the given elements’ slugs and URIs
     *
     * @param Queue|QueueInterface $queue
     * @param ElementQuery|ElementQueryInterface $query
     */
    private function _processElements($queue, $query)
    {
        $this->_totalToProcess += $query->count();
        $elementsService = Craft::$app->getElements();

        foreach ($query->each() as $element) {
            $this->setProgress($queue, $this->_totalProcessed++ / $this->_totalToProcess);

            $oldSlug = $element->slug;
            $oldUri = $element->uri;

            $elementsService->updateElementSlugAndUri($element, $this->updateOtherSites, false, false);

            // Only go deeper if something just changed
            if ($this->updateDescendants && ($element->slug !== $oldSlug || $element->uri !== $oldUri)) {
                $childQuery = $this->_createElementQuery()
                    ->descendantOf($element)
                    ->descendantDist(1);
                $this->_processElements($queue, $childQuery);
            }
        }
    }
}
