<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Element;
use craft\elements\GlobalSet;
use craft\helpers\Cp;
use craft\helpers\Json;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The GlobalsController class is a controller that handles various global and global set related tasks such as saving,
 * deleting displaying both globals and global sets.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class GlobalsController extends Controller
{
    /**
     * Index
     *
     * @return Response
     * @throws ForbiddenHttpException if the user isn't authorized to edit any global sets
     */
    public function actionIndex(): Response
    {
        $editableSets = Craft::$app->getGlobals()->getEditableSets();

        if (empty($editableSets)) {
            throw new ForbiddenHttpException('User not permitted to edit any global content');
        }

        return $this->redirect('globals/' . $editableSets[0]->handle);
    }

    /**
     * Saves a global set.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested global set cannot be found
     * @throws BadRequestHttpException
     */
    public function actionSaveSet(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        $globalSetId = $this->request->getBodyParam('setId');

        if ($globalSetId) {
            $globalSet = Craft::$app->getGlobals()->getSetById($globalSetId);
            if (!$globalSet) {
                throw new BadRequestHttpException("Invalid global set ID: $globalSetId");
            }
        } else {
            $globalSet = new GlobalSet();
        }

        // Set the simple stuff
        $globalSet->name = $this->request->getBodyParam('name');
        $globalSet->handle = $this->request->getBodyParam('handle');

        // Set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = GlobalSet::class;
        $globalSet->setFieldLayout($fieldLayout);

        // Save it
        if (!Craft::$app->getGlobals()->saveSet($globalSet)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t save global set.'));

            // Send the global set back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'globalSet' => $globalSet,
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('app', '{type} saved.', [
            'type' => GlobalSet::displayName(),
        ]));
        return $this->redirectToPostedUrl($globalSet);
    }

    /**
     * Reorders global sets.
     *
     * @return Response
     * @since 3.7.0
     */
    public function actionReorderSets(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $setIds = Json::decode($this->request->getRequiredBodyParam('ids'));
        Craft::$app->getGlobals()->reorderSets($setIds);

        return $this->asSuccess();
    }

    /**
     * Deletes a global set.
     *
     * @return Response
     */
    public function actionDeleteSet(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin();

        $globalSetId = $this->request->getRequiredBodyParam('id');

        Craft::$app->getGlobals()->deleteGlobalSetById($globalSetId);

        return $this->asSuccess();
    }

    /**
     * Edits a global set's content.
     *
     * @param string $globalSetHandle The global set’s handle.
     * @param GlobalSet|null $globalSet The global set being edited, if there were any validation errors.
     * @return Response
     * @throws ForbiddenHttpException if the user is not permitted to edit the global set
     * @throws NotFoundHttpException if the requested site handle is invalid
     */
    public function actionEditContent(string $globalSetHandle, ?GlobalSet $globalSet = null): Response
    {
        $site = Cp::requestedSite();
        if (!$site) {
            throw new ForbiddenHttpException('User not permitted to edit content in any sites');
        }

        // Get the global sets the user is allowed to edit, in the requested site
        $editableGlobalSets = [];

        /** @var GlobalSet[] $globalSets */
        $globalSets = GlobalSet::find()
            ->siteId($site->id)
            ->all();

        foreach ($globalSets as $thisGlobalSet) {
            if (Craft::$app->getUser()->checkPermission('editGlobalSet:' . $thisGlobalSet->uid)) {
                $editableGlobalSets[$thisGlobalSet->handle] = $thisGlobalSet;
            }
        }

        if (empty($editableGlobalSets) || !isset($editableGlobalSets[$globalSetHandle])) {
            throw new ForbiddenHttpException('User not permitted to edit global set');
        }

        if ($globalSet === null) {
            $globalSet = $editableGlobalSets[$globalSetHandle];
        }

        // Prep the form tabs & content
        $form = $globalSet->getFieldLayout()->createForm($globalSet, false, [
            'registerDeltas' => true,
        ]);

        // Render the template!
        return $this->renderTemplate('globals/_edit.twig', [
            'bodyClass' => 'edit-global-set',
            'editableGlobalSets' => $editableGlobalSets,
            'globalSet' => $globalSet,
            'tabs' => $form->getTabMenu(),
            'fieldsHtml' => $form->render(),
        ]);
    }

    /**
     * Saves a global set's content.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested global set cannot be found
     */
    public function actionSaveContent(): ?Response
    {
        $this->requirePostRequest();

        $globalSetId = $this->request->getRequiredBodyParam('setId');
        $siteId = $this->request->getBodyParam('siteId') ?: Craft::$app->getSites()->getPrimarySite()->id;

        $site = Craft::$app->getSites()->getSiteById($siteId);
        $globalSet = Craft::$app->getGlobals()->getSetById($globalSetId, $siteId);

        if (!$globalSet) {
            throw new NotFoundHttpException('Global set not found');
        }

        if (!$site) {
            throw new NotFoundHttpException('Site not found');
        }

        // Make sure the user is allowed to edit this global set and site
        $this->requirePermission('editGlobalSet:' . $globalSet->uid);

        if (Craft::$app->getIsMultiSite()) {
            $this->requirePermission('editSite:' . $site->uid);
        }

        $fieldsLocation = $this->request->getParam('fieldsLocation', 'fields');
        $globalSet->setFieldValuesFromRequest($fieldsLocation);
        $globalSet->setScenario(Element::SCENARIO_LIVE);

        if (!Craft::$app->getElements()->saveElement($globalSet)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t save global set.'));

            // Send the global set back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'globalSet' => $globalSet,
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('app', '{type} saved.', [
            'type' => GlobalSet::displayName(),
        ]));
        return $this->redirectToPostedUrl();
    }
}
