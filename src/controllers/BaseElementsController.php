<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\base\ElementInterface;
use craft\app\errors\InvalidTypeException;
use craft\app\services\Elements;
use craft\app\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

/**
 * The BaseElementsController class provides some common methods for [[ElementsController]] and [[ElementIndexesController]].
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
abstract class BaseElementsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Initializes the application component.
     *
     * @return void
     * @throws ForbiddenHttpException if this is not a Control Panel request
     */
    public function init()
    {
        // Element controllers only support JSON responses
        $this->requireAcceptsJson();

        // Element controllers are only available to the Control Panel
        if (!Craft::$app->getRequest()->getIsCpRequest()) {
            throw new ForbiddenHttpException('Action only available from the Control Panel');
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns the posted element type class.
     *
     * @return ElementInterface
     * @throws BadRequestHttpException if the requested element type is invalid
     */
    protected function getElementType()
    {
        $class = Craft::$app->getRequest()->getRequiredParam('elementType');

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
    protected function getContext()
    {
        return Craft::$app->getRequest()->getParam('context');
    }
}
