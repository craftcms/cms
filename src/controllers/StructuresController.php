<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\ElementInterface;
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
 * @since 3.0.0
 */
class StructuresController extends Controller
{
    /**
     * @var Structure|null
     */
    private ?Structure $_structure = null;

    /**
     * @var ElementInterface|null
     */
    private ?ElementInterface $_element = null;

    /**
     * @inheritdoc
     * @throws NotFoundHttpException if the requested element cannot be found
     */
    public function beforeAction($action): bool
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        // This controller is only available to the control panel
        if (!$this->request->getIsCpRequest()) {
            throw new ForbiddenHttpException('Action only available from the control panel');
        }

        $structureId = $this->request->getRequiredBodyParam('structureId');
        $elementId = $this->request->getRequiredBodyParam('elementId');
        $siteId = $this->request->getRequiredBodyParam('siteId');

        // Make sure they have permission to edit this structure
        $this->requireAuthorization('editStructure:' . $structureId);

        if (($this->_structure = Craft::$app->getStructures()->getStructureById($structureId)) === null) {
            throw new NotFoundHttpException('Structure not found');
        }

        $elementsService = Craft::$app->getElements();

        if (($elementType = $elementsService->getElementTypeById($elementId)) === null) {
            throw new NotFoundHttpException('Element not found');
        }

        /** @var ElementInterface|string $elementType */
        $this->_element = $elementType::find()
            ->drafts(null)
            ->provisionalDrafts(null)
            ->id($elementId)
            ->siteId($siteId)
            ->status(null)
            ->structureId($structureId)
            ->one();

        if (!isset($this->_element)) {
            throw new NotFoundHttpException('Element not found');
        }

        return parent::beforeAction($action);
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
     * @return Response|null
     */
    public function actionMoveElement(): ?Response
    {
        $structuresService = Craft::$app->getStructures();

        $parentElementId = $this->request->getBodyParam('parentId');
        $prevElementId = $this->request->getBodyParam('prevId');

        if ($prevElementId) {
            $prevElement = Craft::$app->getElements()->getElementById($prevElementId, null, $this->_element->siteId);
            $success = $structuresService->moveAfter($this->_structure->id, $this->_element, $prevElement);
        } elseif ($parentElementId) {
            $parentElement = Craft::$app->getElements()->getElementById($parentElementId, null, $this->_element->siteId);
            $success = $structuresService->prepend($this->_structure->id, $this->_element, $parentElement);
        } else {
            $success = $structuresService->prependToRoot($this->_structure->id, $this->_element);
        }

        if ($success) {
            return $this->asSuccess();
        }
        return $this->asFailure();
    }
}
