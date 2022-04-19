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
use craft\errors\OperationAbortedException;
use craft\helpers\Db;
use craft\i18n\Translation;
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
    public array|int|null $elementId = null;

    /**
     * @var string The type of elements to update.
     * @phpstan-var class-string<ElementInterface>
     */
    public string $elementType;

    /**
     * @var int|null The site ID of the elements to update.
     */
    public ?int $siteId = null;

    /**
     * @var bool Whether the elements’ other sites should be updated as well.
     */
    public bool $updateOtherSites = true;

    /**
     * @var bool Whether the elements’ descendants should be updated as well.
     */
    public bool $updateDescendants = true;

    /**
     * @var int The total number of elements we are dealing with.
     */
    private int $_totalToProcess;

    /**
     * @var int The number of elements we've dealt with so far
     */
    private int $_totalProcessed;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
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
    protected function defaultDescription(): ?string
    {
        return Translation::prep('app', 'Updating element slugs and URIs');
    }

    /**
     * Creates an element query for the configured element type.
     *
     * @return ElementQueryInterface
     */
    private function _createElementQuery(): ElementQueryInterface
    {
        /** @var string|ElementInterface $class */
        /** @phpstan-var class-string<ElementInterface>|ElementInterface $class */
        $class = $this->elementType;

        return $class::find()
            ->siteId($this->siteId)
            ->status(null);
    }

    /**
     * Updates the given elements’ slugs and URIs
     *
     * @param Queue|QueueInterface $queue
     * @param ElementQueryInterface $query
     */
    private function _processElements(Queue|QueueInterface $queue, ElementQueryInterface $query): void
    {
        /** @var ElementQueryInterface|ElementQuery $query */
        $this->_totalToProcess += $query->count();
        $elementsService = Craft::$app->getElements();

        foreach (Db::each($query) as $element) {
            /** @var ElementInterface $element */
            $this->setProgress($queue, $this->_totalProcessed++ / $this->_totalToProcess);

            $oldSlug = $element->slug;
            $oldUri = $element->uri;

            try {
                $elementsService->updateElementSlugAndUri($element, $this->updateOtherSites, false, false);
            } catch (OperationAbortedException $e) {
                Craft::warning("Couldn’t update slug and URI for element $element->id: {$e->getMessage()}");
                continue;
            }

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
