<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\web\Controller;
use yii\base\InvalidArgumentException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * DeletionBlockersController returns the blockers that should be addressed before something is deleted in the
 * control panel. `Craft.DeletionBlockersModal` shows them.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class DeletionBlockersController extends Controller
{
    /**
     * Returns the active blockers for a subject.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionGet(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireCpRequest();

        $type = $this->request->getRequiredBodyParam('type');
        $id = $this->request->getRequiredBodyParam('id');
        $service = Craft::$app->getDeletionBlockers();

        try {
            if ($service->getRequiresAdmin($type)) {
                $this->requireAdmin(false);
            }
            $subject = $service->findSubject($type, $id);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage(), previous: $e);
        }

        if ($subject === null) {
            throw new BadRequestHttpException("Invalid $type ID: $id");
        }

        return $this->asJson([
            'blockers' => $service->getBlockerData($service->getBlockers($type, $subject)),
            'label' => $service->getSubjectLabel($type, $subject),
            'headHtml' => $this->view->getHeadHtml(),
            'bodyHtml' => $this->view->getBodyHtml(),
        ]);
    }
}
