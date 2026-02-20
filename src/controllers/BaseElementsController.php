<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementCondition;
use craft\elements\conditions\ElementConditionInterface;
use craft\errors\InvalidTypeException;
use craft\services\ElementSources;
use craft\web\Controller;
use yii\web\BadRequestHttpException;

/**
 * The BaseElementsController class provides some common methods for [[ElementsController]] and [[ElementIndexesController]].
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
abstract class BaseElementsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // All actions require control panel requests
        $this->requireCpRequest();

        return true;
    }

    /**
     * Returns the posted element type class.
     *
     * @return class-string<ElementInterface>
     * @throws BadRequestHttpException if the requested element type is invalid
     */
    protected function elementType(): string
    {
        $class = $this->request->getRequiredParam('elementType');

        // TODO: should probably move the code inside try{} to a helper method
        try {
            if (!is_subclass_of($class, ElementInterface::class)) {
                throw new InvalidTypeException($class, ElementInterface::class);
            }
        } catch (InvalidTypeException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        return $class;
    }

    /**
     * Returns the context that this controller is being called in.
     *
     * @return string
     */
    protected function context(): string
    {
        return $this->request->getParam('context') ?? ElementSources::CONTEXT_INDEX;
    }

    /**
     * Returns the condition that should be applied to the element query.
     *
     * @return ElementConditionInterface|null
     * @since 5.9.0
     */
    protected function condition(): ?ElementConditionInterface
    {
        /** @var array|null $conditionConfig */
        /** @phpstan-var array{class:class-string<ElementConditionInterface>}|null $conditionConfig */
        $conditionConfig = $this->request->getBodyParam('condition');

        if (!$conditionConfig) {
            return null;
        }

        $condition = Craft::$app->getConditions()->createCondition($conditionConfig);

        if ($condition instanceof ElementCondition) {
            $referenceElementId = $this->request->getBodyParam('referenceElementId');
            if ($referenceElementId) {
                $ownerId = $this->request->getBodyParam('referenceElementOwnerId');
                $siteId = $this->request->getBodyParam('referenceElementSiteId');
                $criteria = [];
                if ($ownerId) {
                    $criteria['ownerId'] = $ownerId;
                }
                $condition->referenceElement = Craft::$app->getElements()->getElementById(
                    (int)$referenceElementId,
                    siteId: $siteId,
                    criteria: $criteria,
                );
            }
        }

        return $condition;
    }
}
