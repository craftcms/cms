<?php
/**
 * @link      http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\dates\DateTime;
use craft\app\errors\HttpException;
use craft\app\helpers\TemplateHelper;
use craft\app\helpers\UrlHelper;
use craft\app\models\MailSettings;
use craft\app\elements\GlobalSet;
use craft\app\models\Info;
use craft\app\tools\AssetIndex;
use craft\app\tools\ClearCaches;
use craft\app\tools\DbBackup;
use craft\app\tools\FindAndReplace;
use craft\app\tools\SearchIndex;
use craft\app\web\Response;
use craft\app\web\twig\variables\ToolInfo;
use craft\app\web\Controller;

/**
 * The SystemSettingsController class is a controller that handles various control panel settings related tasks such as
 * displaying, saving and testing Craft settings in the control panel.
 *
 * Note that all actions in this controller require administrator access in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class SystemSettingsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws HttpException if the user isn’t an admin
     */
    public function init()
    {
        // All system setting actions require an admin
        $this->requireAdmin();
    }

    /**
     * Shows the settings index.
     *
     * @return string The rendering result
     */
    public function actionSettingsIndex()
    {
        $tools = [];

        // Only include the Update Asset Indexes tool if there are any asset sources
        if (count(Craft::$app->getVolumes()->getAllVolumes()) !== 0) {
            $tools[] = new ToolInfo(AssetIndex::className());
        }

        $tools[] = new ToolInfo(ClearCaches::className());
        $tools[] = new ToolInfo(DbBackup::className());
        $tools[] = new ToolInfo(FindAndReplace::className());
        $tools[] = new ToolInfo(SearchIndex::className());

        return $this->renderTemplate('settings/_index', [
            'tools' => $tools
        ]);
    }

    /**
     * Shows the general settings form.
     *
     * @param Info $info The info being edited, if there were any validation errors.
     *
     * @return string The rendering result
     */
    public function actionGeneralSettings(Info $info = null)
    {
        if ($info === null) {
            $info = Craft::$app->getInfo();
        }

        // Assemble the timezone options array (Technique adapted from http://stackoverflow.com/a/7022536/1688568)
        $timezoneOptions = [];

        $utc = new DateTime();
        $offsets = [];
        $timezoneIds = [];
        $includedAbbrs = [];

        foreach (\DateTimeZone::listIdentifiers() as $timezoneId) {
            $timezone = new \DateTimeZone($timezoneId);
            $transition = $timezone->getTransitions($utc->getTimestamp(),
                $utc->getTimestamp());
            $abbr = $transition[0]['abbr'];

            $offset = round($timezone->getOffset($utc) / 60);

            if ($offset) {
                $hour = floor($offset / 60);
                $minutes = floor(abs($offset) % 60);

                $format = sprintf('%+d', $hour);

                if ($minutes) {
                    $format .= ':'.sprintf('%02u', $minutes);
                }
            } else {
                $format = '';
            }

            $offsets[] = $offset;
            $timezoneIds[] = $timezoneId;
            $includedAbbrs[] = $abbr;
            $timezoneOptions[$timezoneId] = 'UTC'.$format.($abbr != 'UTC' ? " ({$abbr})" : '').($timezoneId != 'UTC' ? ' - '.$timezoneId : '');
        }

        array_multisort($offsets, $timezoneIds, $timezoneOptions);

        return $this->renderTemplate('settings/general/_index', [
            'info' => $info,
            'timezoneOptions' => $timezoneOptions
        ]);
    }

    /**
     * Saves the general settings.
     *
     * @return void
     */
    public function actionSaveGeneralSettings()
    {
        $this->requirePostRequest();

        $info = Craft::$app->getInfo();

        $info->on = (bool)Craft::$app->getRequest()->getBodyParam('on');
        $info->siteName = Craft::$app->getRequest()->getBodyParam('siteName');
        $info->siteUrl = Craft::$app->getRequest()->getBodyParam('siteUrl');
        $info->timezone = Craft::$app->getRequest()->getBodyParam('timezone');

        if (Craft::$app->saveInfo($info)) {
            Craft::$app->getSession()->setNotice(Craft::t('app',
                'General settings saved.'));
            $this->redirectToPostedUrl();
        } else {
            Craft::$app->getSession()->setError(Craft::t('app',
                'Couldn’t save general settings.'));

            // Send the info back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'info' => $info
            ]);
        }
    }

    /**
     * Renders the email settings page.
     *
     * @param MailSettings|null $settings The posted email settings, if there were any validation errors
     *
*@return Response
     */
    public function actionEditEmailSettings(MailSettings $settings = null)
    {
        if ($settings === null) {
            $settings = new MailSettings();
            $settings->setAttributes(Craft::$app->getSystemSettings()->getSettings('email'), false);
        }

        return $this->renderTemplate('settings/email/_index', [
            'settings' => $settings
        ]);
    }

    /**
     * Saves the email settings.
     *
     * @return Response|null
     */
    public function actionSaveEmailSettings()
    {
        $this->requirePostRequest();

        $settings = $this->_createMailSettingsFromPost();

        if (
            $settings->validate() &&
            Craft::$app->getSystemSettings()->saveSettings('email', $settings->toArray())
        ) {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Email settings saved.'));
            return $this->redirectToPostedUrl();
        }

        Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save email settings.'));

        // Send the settings back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'settings' => $settings
        ]);
    }

    /**
     * Tests the email settings.
     *
     * @return void
     */
    public function actionTestEmailSettings()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $settings = $this->_createMailSettingsFromPost();

        if ($settings->validate()) {
            $mailer = $settings->createMailer();

            $includedSettings = [];

            foreach ($settings->attributes() as $name) {
                if (!empty($settings->$name)) {
                    $includedSettings[] = '<strong>'.$settings->getAttributeLabel($name).':</strong> '.$settings->$name;
                }
            }

            $settingsHtml = implode('<br/>', $includedSettings);

            $message = $mailer
                ->composeFromKey('test_email', ['settings' => TemplateHelper::getRaw($settingsHtml)])
                ->setTo(Craft::$app->getUser()->getIdentity());

            if ($message->send()) {
                return $this->asJson(['success' => true]);
            } else {
                return $this->asErrorJson(Craft::t('app', 'There was an error testing your email settings.'));
            }
        } else {
            return $this->asErrorJson(Craft::t('app', 'Your email settings are invalid.'));
        }
    }

    /**
     * Global Set edit form.
     *
     * @param integer   $globalSetId The global set’s ID, if any.
     * @param GlobalSet $globalSet   The global set being edited, if there were any validation errors.
     *
     * @return string The rendering result
     * @throws HttpException
     */
    public function actionEditGlobalSet($globalSetId = null, GlobalSet $globalSet = null)
    {
        if ($globalSet === null) {
            if ($globalSetId !== null) {
                $globalSet = Craft::$app->getGlobals()->getSetById($globalSetId);

                if (!$globalSet) {
                    throw new HttpException(404);
                }
            } else {
                $globalSet = new GlobalSet();
            }
        }

        if ($globalSet->id) {
            $title = $globalSet->name;
        } else {
            $title = Craft::t('app', 'Create a new global set');
        }

        // Breadcrumbs
        $crumbs = [
            [
                'label' => Craft::t('app', 'Settings'),
                'url' => UrlHelper::getUrl('settings')
            ],
            [
                'label' => Craft::t('app', 'Globals'),
                'url' => UrlHelper::getUrl('settings/globals')
            ]
        ];

        // Tabs
        $tabs = [
            'settings' => [
                'label' => Craft::t('app', 'Settings'),
                'url' => '#set-settings'
            ],
            'fieldlayout' => [
                'label' => Craft::t('app', 'Field Layout'),
                'url' => '#set-fieldlayout'
            ]
        ];

        // Render the template!
        return $this->renderTemplate('settings/globals/_edit', [
            'globalSetId' => $globalSetId,
            'globalSet' => $globalSet,
            'title' => $title,
            'crumbs' => $crumbs,
            'tabs' => $tabs
        ]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Creates a MailSettings model, populated with post data.
     *
     * @return MailSettings
     */
    private function _createMailSettingsFromPost()
    {
        $request = Craft::$app->getRequest();
        $settings = new MailSettings();

        $settings->protocol = $request->getBodyParam('protocol');
        $settings->host = $request->getBodyParam('host');
        $settings->port = $request->getBodyParam('port');
        $settings->useAuthentication = (bool)$request->getBodyParam('useAuthentication');

        if ($settings->useAuthentication && $settings->protocol !== MailSettings::PROTOCOL_GMAIL) {
            $settings->username = $request->getBodyParam('smtpUsername');
            $settings->password = $request->getBodyParam('smtpPassword');
        } else {
            $settings->username = $request->getBodyParam('username');
            $settings->password = $request->getBodyParam('password');
        }

        $settings->encryptionMethod = $request->getBodyParam('encryptionMethod');
        $settings->timeout = $request->getBodyParam('timeout');
        $settings->fromEmail = $request->getBodyParam('fromEmail');
        $settings->fromName = $request->getBodyParam('fromName');

        if (Craft::$app->getEdition() >= Craft::Client) {
            $settings->template = $request->getBodyParam('template');
        }

        return $settings;
    }
}
