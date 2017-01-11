<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\tasks;

use Craft;
use craft\base\Element;
use craft\base\Task;

/**
 * ResaveAllElements represents a Resave All Elements background task.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ResaveAllElements extends Task
{
    // Properties
    // =========================================================================

    /**
     * @var int|null The site ID to fetch the elements in
     */
    public $siteId;

    /**
     * @var string|null Whether only localizable elements should be resaved
     */
    public $localizableOnly;

    /**
     * @var
     */
    private $_elementType;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->siteId === null) {
            $this->siteId = Craft::$app->getSites()->currentSite->id;
        }
    }

    /**
     * @inheritdoc
     */
    public function getTotalSteps(): int
    {
        $this->_elementType = [];
        $localizableOnly = $this->localizableOnly;

        foreach (Craft::$app->getElements()->getAllElementTypes() as $elementType) {
            /** @var Element|string $elementType */
            if (!$localizableOnly || $elementType::isLocalized()) {
                $this->_elementType[] = $elementType;
            }
        }

        return count($this->_elementType);
    }

    /**
     * @inheritdoc
     */
    public function runStep(int $step)
    {
        return $this->runSubTask([
            'type' => ResaveElements::class,
            'elementType' => $this->_elementType[$step],
            'criteria' => [
                'siteId' => $this->siteId,
                'status' => null,
                'enabledForSite' => false
            ]
        ]);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        if ($this->localizableOnly) {
            return Craft::t('app', 'Resaving all localizable elements');
        }

        return Craft::t('app', 'Resaving all elements');
    }
}
