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
                if (!Craft::$app->getElements()->saveElement($element)) {
                    throw new Exception('Couldn’t save element '.$element->id.' ('.get_class($element).') due to validation errors.');
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
        return Craft::t('app', 'Resaving {class} elements', [
            'class' => App::humanizeClass($this->elementType)
        ]);
    }
}
