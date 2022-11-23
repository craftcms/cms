<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\ElementInterface;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * Relational fields controller.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class RelationalFieldsController extends Controller
{
    /**
     * Returns HTML for a structured elements field input based on a given list
     * of selected element ids.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    public function actionStructuredInputHtml(): Response
    {
        $this->requireCpRequest();
        $this->requireAcceptsJson();

        $elementType = $this->request->getRequiredParam('elementType');
        $elementIds = $this->request->getParam('elementIds', []);

        $elements = [];

        if (!empty($elementIds)) {
            /** @var ElementInterface[] $elements */
            $elements = $elementType::find()
                ->id($elementIds)
                ->siteId($this->request->getParam('siteId'))
                ->status(null)
                ->all();

            // Fill in the gaps
            $structuresService = Craft::$app->getStructures();
            $structuresService->fillGapsInElements($elements);

            // Enforce the branch limit
            if ($branchLimit = $this->request->getParam('branchLimit')) {
                $structuresService->applyBranchLimitToElements($elements, $branchLimit);
            }
        }

        $html = $this->getView()->renderTemplate('_includes/forms/elementSelect.twig', [
            'elements' => $elements,
            'id' => $this->request->getParam('containerId'),
            'name' => $this->request->getParam('name'),
            'selectionLabel' => $this->request->getParam('selectionLabel'),
            'elementType' => $elementType,
            'maintainHierarchy' => true,
        ]);

        return $this->asJson([
            'html' => $html,
        ]);
    }
}
