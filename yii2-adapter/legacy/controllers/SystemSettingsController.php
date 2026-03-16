<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\elements\GlobalSet;
use craft\helpers\UrlHelper;
use craft\web\assets\admintable\AdminTableAsset;
use craft\web\Controller;
use CraftCms\Cms\Cms;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

use function CraftCms\Cms\t;

/**
 * The SystemSettingsController class is a controller that handles various control panel settings related tasks such as
 * displaying, saving and testing Craft settings in the control panel.
 * Note that all actions in this controller require administrator access in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SystemSettingsController extends Controller
{
    private bool $readOnly;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (in_array($action->id, [
            'edit-global-set',
            'global-set-index',
        ])) {
            // Some actions require admin but not allowAdminChanges
            $this->requireAdmin(false);
        } else {
            // All other actions require an admin & allowAdminChanges
            $this->requireAdmin();
        }

        $this->readOnly = !Cms::config()->allowAdminChanges;

        return true;
    }

    /**
     * Global Set index
     *
     * @return Response
     * @since 5.3.0
     * @deprecated in 6.0.0
     */
    public function actionGlobalSetIndex(): Response
    {
        $view = $this->getView();
        $view->registerAssetBundle(AdminTableAsset::class);
        return $this->rendertemplate('yii2-adapter/settings/globals/_index', [
            'title' => t('Globals', category: 'yii2-adapter'),
            'crumbs' => [
                [
                    'label' => t('Settings'),
                    'url' => UrlHelper::cpUrl('settings'),
                ],
            ],
            'globalSets' => Craft::$app->getGlobals()->getAllSets(),
            'buttonLabel' => mb_ucfirst(t('New {type}', [
                'type' => GlobalSet::lowerDisplayName(),
            ])),
            'readOnly' => $this->readOnly,
        ]);
    }

    /**
     * Global Set edit form.
     *
     * @param int|null $globalSetId The global set’s ID, if any.
     * @param GlobalSet|null $globalSet The global set being edited, if there were any validation errors.
     * @return Response
     * @throws NotFoundHttpException if the requested global set cannot be found
     * @deprecated in 6.0.0
     */
    public function actionEditGlobalSet(?int $globalSetId = null, ?GlobalSet $globalSet = null): Response
    {
        if ($globalSetId === null && $this->readOnly) {
            throw new ForbiddenHttpException('Administrative changes are disallowed in this environment.');
        }

        if ($globalSet === null) {
            if ($globalSetId !== null) {
                $globalSet = Craft::$app->getGlobals()->getSetById($globalSetId);

                if (!$globalSet) {
                    throw new NotFoundHttpException('Global set not found');
                }
            } else {
                $globalSet = new GlobalSet();
            }
        }

        if ($globalSet->id) {
            $title = trim($globalSet->name) ?: t('Edit {type}', [
                'type' => GlobalSet::displayName(),
            ]);
        } else {
            $title = t('Create a new {type}', [
                'type' => GlobalSet::lowerDisplayName(),
            ]);
        }

        // Breadcrumbs
        $crumbs = [
            [
                'label' => t('Settings'),
                'url' => UrlHelper::url('settings'),
            ],
            [
                'label' => t('Globals', category: 'yii2-adapter'),
                'url' => UrlHelper::url('settings/globals'),
            ],
        ];

        // Render the template!
        return $this->rendertemplate('yii2-adapter/settings/globals/_edit', [
            'globalSetId' => $globalSetId,
            'globalSet' => $globalSet,
            'title' => $title,
            'crumbs' => $crumbs,
            'readOnly' => $this->readOnly,
        ]);
    }
}
