<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use craft\base\ElementInterface;
use craft\web\Controller;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Support\Facades\Structures;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use function CraftCms\Cms\template;

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
            Structures::fillGapsInElements($elements);

            // Enforce the branch limit
            if ($branchLimit = $this->request->getParam('branchLimit')) {
                Structures::applyBranchLimitToElements($elements, $branchLimit);
            }
        }

        app(Drafts::class)->loadProvisionalChanges($elements);

        $html = template('_includes/forms/elementSelect', [
            'elements' => $elements,
            'id' => $this->request->getParam('containerId'),
            'name' => $this->request->getParam('name'),
            'selectionLabel' => $this->request->getParam('selectionLabel'),
            'elementType' => $elementType,
            'maintainHierarchy' => true,
            'registerJs' => false,
        ]);

        return $this->asJson([
            'html' => $html,
            'headHtml' => $this->getView()->getHeadHtml(),
            'bodyHtml' => $this->getView()->getBodyHtml(),
        ]);
    }
}
