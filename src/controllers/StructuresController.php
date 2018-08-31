<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Element;
use craft\models\Structure;
use craft\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The StructuresController class is a controller that handles structure related tasks such as moving an element within
 * a structure.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class StructuresController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @var Structure|null
     */
    private $_structure;

    /**
     * @var Element|null
     */
    private $_element;

    // Public Methods
    // =========================================================================

    /**
     * Initializes the application component.
     *
     * @throws ForbiddenHttpException if this is not a Control Panel request
     * @throws NotFoundHttpException if the requested element cannot be found
     */
    public function init()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();

        // This controller is only available to the Control Panel
        if (!$request->getIsCpRequest()) {
            throw new ForbiddenHttpException('Action only available from the Control Panel');
        }

        $structureId = $request->getRequiredBodyParam('structureId');
        $elementId = $request->getRequiredBodyParam('elementId');
        $siteId = $request->getRequiredBodyParam('siteId');

        // Make sure they have permission to edit this structure
        $this->requireAuthorization('editStructure:' . $structureId);

        if (($this->_structure = Craft::$app->getStructures()->getStructureById($structureId)) === null) {
            throw new NotFoundHttpException('Structure not found');
        }

        $elementsService = Craft::$app->getElements();

        if (($elementType = $elementsService->getElementTypeById($elementId)) === null) {
            throw new NotFoundHttpException('Element not found');
        }

        /** @var Element|string $elementType */
        $this->_element = $elementType::find()
            ->id($elementId)
            ->siteId($siteId)
            ->anyStatus()
            ->structureId($structureId)
            ->one();

        if ($this->_element === null) {
            throw new NotFoundHttpException('Element not found');
        }
    }

    /**
     * Returns the descendant level delta for a given element.
     *
     * @return Response
     */
    public function actionGetElementLevelDelta(): Response
    {
        $delta = Craft::$app->getStructures()->getElementLevelDelta($this->_structure->id, $this->_element);

        return $this->asJson(compact('delta'));
    }

    /**
     * Moves an element within a structure.
     *
     * @return Response
     */
    public function actionMoveElement(): Response
    {
        $request = Craft::$app->getRequest();
        $structuresService = Craft::$app->getStructures();

        $parentElementId = $request->getBodyParam('parentId');
        $prevElementId = $request->getBodyParam('prevId');

        if ($prevElementId) {
            $prevElement = Craft::$app->getElements()->getElementById($prevElementId, null, $this->_element->siteId);
            $success = $structuresService->moveAfter($this->_structure->id, $this->_element, $prevElement, 'auto');
        } else if ($parentElementId) {
            $parentElement = Craft::$app->getElements()->getElementById($parentElementId, null, $this->_element->siteId);
            $success = $structuresService->prepend($this->_structure->id, $this->_element, $parentElement, 'auto');
        } else {
            $success = $structuresService->prependToRoot($this->_structure->id, $this->_element, 'auto');
        }

        return $this->asJson(compact('success'));
    }
}
