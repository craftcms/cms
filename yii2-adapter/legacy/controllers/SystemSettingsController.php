<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\elements\GlobalSet;
use craft\errors\MissingComponentException;
use craft\helpers\App;
use craft\helpers\Component;
use craft\helpers\MailerHelper;
use craft\helpers\UrlHelper;
use craft\mail\transportadapters\BaseTransportAdapter;
use craft\mail\transportadapters\Sendmail;
use craft\mail\transportadapters\TransportAdapterInterface;
use craft\models\MailSettings;
use craft\web\assets\admintable\AdminTableAsset;
use craft\web\Controller;
use CraftCms\Cms\Cms;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\Facades\Config;
use yii\base\Exception;
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
            'edit-email-settings',
            'edit-global-set',
            'global-set-index',
            'test-email-settings',
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
     * Renders the email settings page.
     *
     * @param MailSettings|null $settings The posted email settings, if there were any validation errors
     * @param TransportAdapterInterface|null $adapter The transport adapter, if there were any validation errors
     * @return Response
     * @throws Exception if a plugin returns an invalid mail transport type
     */
    public function actionEditEmailSettings(?MailSettings $settings = null, ?TransportAdapterInterface $adapter = null): Response
    {
        if ($settings === null) {
            $settings = App::mailSettings();
        }

        if ($adapter === null) {
            try {
                $adapter = MailerHelper::createTransportAdapter($settings->transportType, $settings->transportSettings);
            } catch (MissingComponentException) {
                $adapter = new Sendmail();
                $adapter->errors()->add('type', t('The transport type “{type}” could not be found.', [
                    'type' => $settings->transportType,
                ]));
            }
        }

        // Get all the registered transport adapter types
        $allTransportAdapterTypes = MailerHelper::allMailerTransportTypes();

        // Make sure the selected adapter class is in there
        if (!in_array(get_class($adapter), $allTransportAdapterTypes, true)) {
            $allTransportAdapterTypes[] = get_class($adapter);
        }

        $allTransportAdapters = [];
        $transportTypeOptions = [];

        foreach ($allTransportAdapterTypes as $transportAdapterType) {
            /** @var class-string<TransportAdapterInterface> $transportAdapterType */
            if ($transportAdapterType === get_class($adapter) || $transportAdapterType::isSelectable()) {
                $allTransportAdapters[] = MailerHelper::createTransportAdapter($transportAdapterType);
                $transportTypeOptions[] = [
                    'value' => $transportAdapterType,
                    'label' => $transportAdapterType::displayName(),
                ];
            }
        }

        // Sort them by name
        $transportTypeOptions = Arr::sort($transportTypeOptions, 'label');

        // See if it looks like config/app.php is overriding the mailer component
        $customMailerFiles = [];
        foreach (['app', 'app.web', 'app.console'] as $file) {
            if (Config::has("craft.$file.components.mailer")) {
                $customMailerFiles[] = config_path("$file.php");
            }
        }

        return $this->rendertemplate('settings/email/_index', [
            'settings' => $settings,
            'adapter' => $adapter,
            'transportTypeOptions' => $transportTypeOptions,
            'allTransportAdapters' => $allTransportAdapters,
            'customMailerFiles' => $customMailerFiles,
            'readOnly' => $this->readOnly,
        ]);
    }

    /**
     * Saves the email settings.
     *
     * @return Response|null
     */
    public function actionSaveEmailSettings(): ?Response
    {
        $this->requirePostRequest();

        $settings = $this->_createMailSettingsFromPost();
        $settingsAreValid = $settings->validate();

        /** @var BaseTransportAdapter $adapter */
        $adapter = MailerHelper::createTransportAdapter($settings->transportType, $settings->transportSettings);
        $adapterIsValid = $adapter->validate();

        if (!$settingsAreValid || !$adapterIsValid) {
            $this->setFailFlash(t('Couldn’t save email settings.'));

            // Send the settings back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings,
                'adapter' => $adapter,
            ]);

            return null;
        }

        app(ProjectConfig::class)->set('email', $settings->toArray(), 'Update email settings.');

        $this->setSuccessFlash(t('Email settings saved.'));
        return $this->redirectToPostedUrl();
    }

    /**
     * Tests the email settings.
     */
    public function actionTestEmailSettings(): void
    {
        if (Cms::config()->allowAdminChanges) {
            $this->requirePostRequest();

            $settings = $this->_createMailSettingsFromPost();
            $settingsIsValid = $settings->validate();

            /** @var BaseTransportAdapter $adapter */
            $adapter = MailerHelper::createTransportAdapter($settings->transportType, $settings->transportSettings);
            $adapterIsValid = $adapter->validate();

            if ($settingsIsValid && $adapterIsValid) {
                $mailer = Craft::createObject(App::mailerConfig($settings));
            } else {
                $this->setFailFlash(t('Your email settings are invalid.'));
            }
        } else {
            $mailer = Craft::$app->getMailer();
        }

        // Try to send the test email
        if (isset($mailer)) {
            $message = $mailer
                ->composeFromKey('test_email', [
                    'settings' => MailerHelper::settingsReport($mailer, $adapter ?? null),
                ])
                ->setTo(static::currentUser());

            if ($message->send()) {
                $this->setSuccessFlash(t('Email sent successfully! Check your inbox.'));
            } else {
                $this->setFailFlash(t('There was an error testing your email settings.'));
            }
        }

        // Send the settings back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'settings' => $settings ?? null,
            'adapter' => $adapter ?? null,
        ]);
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
        $view->registerTranslations('yii2-adapter', [
            'Global Set Name',
            'No global sets exist yet.',
        ]);

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

    /**
     * Creates a MailSettings model, populated with post data.
     *
     * @return MailSettings
     */
    private function _createMailSettingsFromPost(): MailSettings
    {
        $settings = new MailSettings();

        $settings->fromEmail = $this->request->getBodyParam('fromEmail');
        $settings->replyToEmail = $this->request->getBodyParam('replyToEmail') ?: null;
        $settings->fromName = $this->request->getBodyParam('fromName');
        $settings->template = $this->request->getBodyParam('template');
        $settings->transportType = $this->request->getBodyParam('transportType');
        $settings->transportSettings = Component::cleanseConfig($this->request->getBodyParam(sprintf('transportTypes.%s', Html::id($settings->transportType))) ?? []);
        $settings->siteOverrides = array_filter(array_map(
            fn(array $overrides) => array_filter($overrides),
            $this->request->getBodyParam('siteOverrides') ?? [],
        ));

        return $settings;
    }
}
