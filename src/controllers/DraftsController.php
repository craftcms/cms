<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\ElementInterface;
use craft\behaviors\DraftBehavior;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * Drafts controller.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
class DraftsController extends Controller
{
    /**
     * Merges recent source element changes into a draft.
     *
     * @return Response
     * @throws ForbiddenHttpException
     * @throws BadRequestHttpException
     */
    public function actionMergeSourceChanges(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        /** @var ElementInterface|string $elementType */
        $elementType = $request->getRequiredBodyParam('elementType');
        $draftId = $request->getRequiredBodyParam('draftId');
        $siteId = $request->getBodyParam('siteId');
        $this->requireAuthorization('mergeDraftSourceChanges:' . $draftId);

        /** @var ElementInterface|DraftBehavior $elementType */
        $draft = $elementType::find()
            ->draftId($draftId)
            ->siteId($siteId)
            ->anyStatus()
            ->one();

        if (!$draft) {
            throw new BadRequestHttpException('Invalid draft ID: ' . $draftId);
        }

        Craft::$app->getDrafts()->mergeSourceChanges($draft);

        // Redirect to the requested URL to reload the draft
        Craft::$app->getSession()->setNotice(Craft::t('app', 'Recent {type} changes merged.', [
            'type' => $elementType::lowerDisplayName(),
        ]));

        return $this->asJson(['success' => true]);
    }
}
