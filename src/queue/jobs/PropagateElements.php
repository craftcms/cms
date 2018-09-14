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
use craft\helpers\App;
use craft\queue\BaseJob;

/**
 * PropagateElements job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class PropagateElements extends BaseJob
{
    // Properties
    // =========================================================================

    /**
     * @var string|ElementInterface The element type that should be propagated
     */
    public $elementType;

    /**
     * @var array|null The element criteria that determines which elements should be propagated
     */
    public $criteria;

    /**
     * @var int|int[] The site ID(s) that the elements should be propagated to
     */
    public $siteId;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        $class = $this->elementType;

        // Let's save ourselves some trouble and just clear all the caches for this element class
        Craft::$app->getTemplateCaches()->deleteCachesByElementType($class);

        // Now find the affected element IDs
        /** @var ElementQuery $query */
        $query = $class::find();
        if (!empty($this->criteria)) {
            Craft::configure($query, $this->criteria);
        }
        $query
            ->offset(null)
            ->limit(null)
            ->orderBy(null);

        $totalElements = $query->count();
        $currentElement = 0;

        try {
            foreach ($query->each() as $element) {
                $this->setProgress($queue, $currentElement++ / $totalElements);

                /** @var Element $element */
                $element->setScenario(Element::SCENARIO_ESSENTIALS);
                foreach ((array)$this->siteId as $siteId) {
                    Craft::$app->getElements()->propagateElement($element, $siteId);
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
        return Craft::t('app', 'Propagating {class} elements', [
            'class' => App::humanizeClass($this->elementType)
        ]);
    }
}
