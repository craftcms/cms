<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\elements\GlobalSet;
use craft\app\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The GlobalsController class is a controller that handles various global and global set related tasks such as saving,
 * deleting displaying both globals and global sets.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class GlobalsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Saves a global set.
     *
     * @return Response|null
     *
     * @throws NotFoundHttpException if the requested global set cannot be found
     */
    public function actionSaveSet()
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        $globalSetId = Craft::$app->getRequest()->getBodyParam('setId');

        if ($globalSetId) {
            $globalSet = Craft::$app->getGlobals()->getSetById($globalSetId);

            if (!$globalSet) {
                throw new NotFoundHttpException('Global set not found');
            }
        } else {
            $globalSet = new GlobalSet();
        }

        // Set the simple stuff
        $globalSet->name = Craft::$app->getRequest()->getBodyParam('name');
        $globalSet->handle = Craft::$app->getRequest()->getBodyParam('handle');

        // Set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = GlobalSet::className();
        $globalSet->setFieldLayout($fieldLayout);

        // Save it
        if (Craft::$app->getGlobals()->saveSet($globalSet)) {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Global set saved.'));

            return $this->redirectToPostedUrl($globalSet);
        }

        Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save global set.'));

        // Send the global set back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'globalSet' => $globalSet
        ]);

        return null;
    }

    /**
     * Deletes a global set.
     *
     * @return Response
     */
    public function actionDeleteSet()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();
        $this->requireAdmin();

        $globalSetId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Craft::$app->getGlobals()->deleteSetById($globalSetId);

        return $this->asJson(['success' => true]);
    }

    /**
     * Edits a global set's content.
     *
     * @param string    $globalSetHandle The global set’s handle.
     * @param string    $localeId        The locale ID, if specified.
     * @param GlobalSet $globalSet       The global set being edited, if there were any validation errors.
     *
     * @return string The rendering result
     * @throws ForbiddenHttpException if the user is not permitted to edit the global set
     */
    public function actionEditContent($globalSetHandle, $localeId = null, GlobalSet $globalSet = null)
    {
        // Get the locales the user is allowed to edit
        $editableLocaleIds = Craft::$app->getI18n()->getEditableLocaleIds();

        // Editing a specific locale?
        if ($localeId) {
            // Make sure the user has permission to edit that locale
            if (!in_array($localeId, $editableLocaleIds)) {
                throw new ForbiddenHttpException('User not permitted to edit content in any locales');
            }
        } else {
            // Are they allowed to edit the current app locale?
            if (in_array(Craft::$app->language, $editableLocaleIds)) {
                $localeId = Craft::$app->language;
            } else {
                // Just use the first locale they are allowed to edit
                $localeId = $editableLocaleIds[0];
            }
        }

        // Get the global sets the user is allowed to edit, in the requested locale
        $editableGlobalSets = [];

        $globalSets = GlobalSet::find()
            ->locale($localeId)
            ->all();

        foreach ($globalSets as $thisGlobalSet) {
            if (Craft::$app->getUser()->checkPermission('editGlobalSet:'.$thisGlobalSet->id)) {
                $editableGlobalSets[$thisGlobalSet->handle] = $thisGlobalSet;
            }
        }

        if (!$editableGlobalSets || !isset($editableGlobalSets[$globalSetHandle])) {
            throw new ForbiddenHttpException('User not permitted to edit global set');
        }

        if ($globalSet === null) {
            $globalSet = $editableGlobalSets[$globalSetHandle];
        }

        // Render the template!
        return $this->renderTemplate('globals/_edit', [
            'globalSetHandle' => $globalSetHandle,
            'localeId' => $localeId,
            'globalSets' => $globalSets,
            'globalSet' => $globalSet
        ]);
    }

    /**
     * Saves a global set's content.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested global set cannot be found
     */
    public function actionSaveContent()
    {
        $this->requirePostRequest();

        $globalSetId = Craft::$app->getRequest()->getRequiredBodyParam('setId');
        $localeId = Craft::$app->getRequest()->getBodyParam('locale', Craft::$app->getI18n()->getPrimarySiteLocaleId());

        // Make sure the user is allowed to edit this global set and locale
        $this->requirePermission('editGlobalSet:'.$globalSetId);

        if (Craft::$app->isLocalized()) {
            $this->requirePermission('editLocale:'.$localeId);
        }

        $globalSet = Craft::$app->getGlobals()->getSetById($globalSetId, $localeId);

        if (!$globalSet) {
            throw new NotFoundHttpException('Global set not found');
        }

        $globalSet->setFieldValuesFromPost('fields');

        if (Craft::$app->getGlobals()->saveContent($globalSet)) {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Globals saved.'));

            return $this->redirectToPostedUrl();
        }

        Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save globals.'));

        // Send the global set back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'globalSet' => $globalSet,
        ]);

        return null;
    }
}
