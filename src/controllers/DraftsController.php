<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\db\Table;
use craft\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Drafts controller.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
class DraftsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Renames a draft.
     *
     * @return Response
     */
    public function actionUpdateDraftMeta(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $draftId = $request->getRequiredBodyParam('draftId');
        $this->requireAuthorization('editDraft:' . $draftId);

        $name = $request->getRequiredBodyParam('name');
        $notes = $request->getBodyParam('notes');

        Craft::$app->getDrafts()->updateDraftName($draftId, $name, $notes);
        return $this->asJson(['success' => true]);
    }
}
