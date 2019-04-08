<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\QueryAbortedException;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\queue\BaseJob;
use yii\base\Exception;

/**
 * ResaveElements job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ResaveElements extends BaseJob
{
    // Properties
    // =========================================================================

    /**
     * @var string|ElementInterface|null The element type that should be resaved
     */
    public $elementType;

    /**
     * @var array|null The element criteria that determines which elements should be resaved
     */
    public $criteria;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        // Let's save ourselves some trouble and just clear all the caches for this element class
        Craft::$app->getTemplateCaches()->deleteCachesByElementType($this->elementType);

        /** @var ElementQuery $query */
        $query = $this->_query();
        $totalElements = $query->count();
        $elementsService = Craft::$app->getElements();
        $currentElement = 0;

        try {
            foreach ($query->each() as $element) {
                $this->setProgress($queue, $currentElement++ / $totalElements);

                /** @var Element $element */
                $element->setScenario(Element::SCENARIO_ESSENTIALS);
                $element->resaving = true;
                if (!$elementsService->saveElement($element)) {
                    throw new Exception('Couldn’t save element ' . $element->id . ' (' . get_class($element) . ') due to validation errors.');
                }
            }
        } catch (QueryAbortedException $e) {
            // Fail silently
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        /** @var ElementQuery $query */
        $query = $this->_query();
        /** @var ElementInterface $elementType */
        $elementType = $query->elementType;
        $totalElements = $query->count();
        return Craft::t('app', 'Resaving {type}', [
            'type' => mb_strtolower($totalElements == 1 ? $elementType::displayName() : $elementType::pluralDisplayName()),
        ]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the element query based on the criteria.
     *
     * @return ElementQueryInterface
     */
    private function _query(): ElementQueryInterface
    {
        $query = $this->elementType::find();

        if (!empty($this->criteria)) {
            Craft::configure($query, $this->criteria);
        }

        $query
            ->offset(null)
            ->limit(null)
            ->orderBy(null);

        return $query;
    }
}
