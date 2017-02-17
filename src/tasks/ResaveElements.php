<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\tasks;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Task;
use craft\elements\db\ElementQuery;
use craft\helpers\App;
use craft\helpers\StringHelper;

/**
 * ResaveElements represents a Resave Elements background task.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ResaveElements extends Task
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

    /**
     * @var int|null
     */
    private $_siteId;

    /**
     * @var int[]|null
     */
    private $_elementIds;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTotalSteps(): int
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

        $this->_siteId = $query->siteId;
        $this->_elementIds = $query->ids();

        return count($this->_elementIds);
    }

    /**
     * @inheritdoc
     */
    public function runStep(int $step)
    {
        try {
            /** @var Element $element */
            $elementId = $this->_elementIds[$step];
            $element = Craft::$app->getElements()->getElementById($elementId, $this->elementType, $this->_siteId);

            if (!$element) {
                return true;
            }

            if (!Craft::$app->getElements()->saveElement($element, false)) {
                $errorMessage = 'Encountered the following validation errors when trying to save '.get_class($element).' element "'.$element.'" with the ID "'.$element->id.'":';

                foreach ($element->getErrors() as $attribute => $errors) {
                    foreach ($errors as $error) {
                        $errorMessage .= "\n - {$error}";
                    }
                }

                return $errorMessage;
            }

            return true;
        } catch (\Exception $e) {
            $class = $this->elementType;

            return 'An exception was thrown while trying to save the '.StringHelper::toLowerCase($class::displayName()).' with the ID “'.$this->_elementIds[$step].'”: '.$e->getMessage();
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
