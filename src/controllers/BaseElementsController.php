<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\ElementInterface;
use craft\errors\InvalidTypeException;
use craft\web\Controller;
use yii\web\BadRequestHttpException;

/**
 * The BaseElementsController class provides some common methods for [[ElementsController]] and [[ElementIndexesController]].
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class BaseElementsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Initializes the application component.
     *
     * @throws BadRequestHttpException
     */
    public function init()
    {
        $this->requireAcceptsJson();
        $this->requireCpRequest();
        parent::init();
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns the posted element type class.
     *
     * @return string
     * @throws BadRequestHttpException if the requested element type is invalid
     */
    protected function elementType(): string
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
     * @return string|null
     */
    protected function context()
    {
        return Craft::$app->getRequest()->getParam('context');
    }
}
