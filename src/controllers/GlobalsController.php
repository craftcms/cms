<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Element;
use craft\base\Field;
use craft\elements\GlobalSet;
use craft\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The GlobalsController class is a controller that handles various global and global set related tasks such as saving,
 * deleting displaying both globals and global sets.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class GlobalsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Index
     *
     * @return Response
     * @throws ForbiddenHttpException if the user isn't authorized to edit any global sets
     */
    public function actionIndex()
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
        $fieldLayout->type = GlobalSet::class;
        $globalSet->setFieldLayout($fieldLayout);

        // Save it
        if (!Craft::$app->getGlobals()->saveSet($globalSet)) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save global set.'));

            // Send the global set back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'globalSet' => $globalSet
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Global set saved.'));

        return $this->redirectToPostedUrl($globalSet);
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

        $globalSetId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Craft::$app->getElements()->deleteElementById($globalSetId, GlobalSet::class);

        return $this->asJson(['success' => true]);
    }

    /**
     * Edits a global set's content.
     *
     * @param string $globalSetHandle The global set’s handle.
     * @param string|null $siteHandle The site handle, if specified.
     * @param GlobalSet|null $globalSet The global set being edited, if there were any validation errors.
     * @return Response
     * @throws ForbiddenHttpException if the user is not permitted to edit the global set
     * @throws NotFoundHttpException if the requested site handle is invalid
     */
    public function actionEditContent(string $globalSetHandle, string $siteHandle = null, GlobalSet $globalSet = null): Response
    {
        if (Craft::$app->getIsMultiSite()) {
            // Get the sites the user is allowed to edit
            $editableSiteIds = Craft::$app->getSites()->getEditableSiteIds();

            if (empty($editableSiteIds)) {
                throw new ForbiddenHttpException('User not permitted to edit content in any sites');
            }

            // Editing a specific site?
            if ($siteHandle !== null) {
                $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

                if (!$site) {
                    throw new NotFoundHttpException('Invalid site handle: ' . $siteHandle);
                }

                // Make sure the user has permission to edit that site
                if (!in_array($site->id, $editableSiteIds, false)) {
                    throw new ForbiddenHttpException('User not permitted to edit content in this site');
                }
            } else {
                // Are they allowed to edit the current site?
                /** @noinspection PhpUnhandledExceptionInspection */
                $currentSite = Craft::$app->getSites()->getCurrentSite();
                if (in_array($currentSite->id, $editableSiteIds, false)) {
                    $site = $currentSite;
                } else {
                    // Just use the first site they are allowed to edit
                    $site = Craft::$app->getSites()->getSiteById($editableSiteIds[0]);
                }
            }
        } else {
            /** @noinspection PhpUnhandledExceptionInspection */
            $site = Craft::$app->getSites()->getPrimarySite();
        }

        // Get the global sets the user is allowed to edit, in the requested site
        $editableGlobalSets = [];

        $globalSets = GlobalSet::find()
            ->siteId($site->id)
            ->all();

        foreach ($globalSets as $thisGlobalSet) {
            if (Craft::$app->getUser()->checkPermission('editGlobalSet:' . $thisGlobalSet->id)) {
                $editableGlobalSets[$thisGlobalSet->handle] = $thisGlobalSet;
            }
        }

        if (empty($editableGlobalSets) || !isset($editableGlobalSets[$globalSetHandle])) {
            throw new ForbiddenHttpException('User not permitted to edit global set');
        }

        if ($globalSet === null) {
            $globalSet = $editableGlobalSets[$globalSetHandle];
        }

        // Define the content tabs
        // ---------------------------------------------------------------------

        $tabs = [];

        foreach ($globalSet->getFieldLayout()->getTabs() as $index => $tab) {
            // Do any of the fields on this tab have errors?
            $hasErrors = false;

            if ($globalSet->hasErrors()) {
                foreach ($tab->getFields() as $field) {
                    /** @var Field $field */
                    if ($hasErrors = $globalSet->hasErrors($field->handle . '.*')) {
                        break;
                    }
                }
            }

            $tabs[] = [
                'label' => Craft::t('site', $tab->name),
                'url' => '#' . $tab->getHtmlId(),
                'class' => $hasErrors ? 'error' : null
            ];
        }

        // Render the template!
        return $this->renderTemplate('globals/_edit', compact(
            'editableGlobalSets',
            'globalSet',
            'tabs'
        ));
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
        $siteId = Craft::$app->getRequest()->getBodyParam('siteId') ?: Craft::$app->getSites()->getPrimarySite()->id;

        // Make sure the user is allowed to edit this global set and site
        $this->requirePermission('editGlobalSet:' . $globalSetId);

        if (Craft::$app->getIsMultiSite()) {
            $this->requirePermission('editSite:' . $siteId);
        }

        $globalSet = Craft::$app->getGlobals()->getSetById($globalSetId, $siteId);

        if (!$globalSet) {
            throw new NotFoundHttpException('Global set not found');
        }

        $globalSet->setFieldValuesFromRequest('fields');
        $globalSet->setScenario(Element::SCENARIO_LIVE);

        if (!Craft::$app->getElements()->saveElement($globalSet)) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save global set.'));

            // Send the global set back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'globalSet' => $globalSet,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Global set saved.'));

        return $this->redirectToPostedUrl();
    }
}
