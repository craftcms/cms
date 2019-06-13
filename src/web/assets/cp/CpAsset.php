<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\cp;

use Craft;
use craft\config\GeneralConfig;
use craft\elements\User;
use craft\helpers\Assets;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\i18n\Locale;
use craft\models\Section;
use craft\services\Sites;
use craft\web\AssetBundle;
use craft\web\assets\d3\D3Asset;
use craft\web\assets\datepickeri18n\DatepickerI18nAsset;
use craft\web\assets\elementresizedetector\ElementResizeDetectorAsset;
use craft\web\assets\fabric\FabricAsset;
use craft\web\assets\fileupload\FileUploadAsset;
use craft\web\assets\garnish\GarnishAsset;
use craft\web\assets\jquerypayment\JqueryPaymentAsset;
use craft\web\assets\jquerytouchevents\JqueryTouchEventsAsset;
use craft\web\assets\jqueryui\JqueryUiAsset;
use craft\web\assets\picturefill\PicturefillAsset;
use craft\web\assets\selectize\SelectizeAsset;
use craft\web\assets\velocity\VelocityAsset;
use craft\web\assets\xregexp\XregexpAsset;
use craft\web\View;
use yii\web\JqueryAsset;

/**
 * Asset bundle for the Control Panel
 */
class CpAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__ . '/dist';

        $this->depends = [
            D3Asset::class,
            ElementResizeDetectorAsset::class,
            GarnishAsset::class,
            JqueryAsset::class,
            JqueryTouchEventsAsset::class,
            JqueryUiAsset::class,
            JqueryPaymentAsset::class,
            DatepickerI18nAsset::class,
            PicturefillAsset::class,
            SelectizeAsset::class,
            VelocityAsset::class,
            FileUploadAsset::class,
            XregexpAsset::class,
            FabricAsset::class,
        ];

        $this->css = [
            'css/craft.css',
            'css/charts.css',
        ];

        $this->js[] = 'js/Craft' . $this->dotJs();

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view)
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $this->_registerTranslations($view);
        }

        // Define the Craft object
        $craftJson = Json::encode($this->_craftData(), JSON_UNESCAPED_UNICODE);
        $js = <<<JS
window.Craft = {$craftJson};
JS;
        $view->registerJs($js, View::POS_HEAD);
    }

    // Private Methods
    // =========================================================================

    private function _registerTranslations(View $view)
    {
        $view->registerTranslations('app', [
            '(blank)',
            '1 Available Update',
            '{first}-{last} of {total}',
            'Actions',
            'All',
            'An unknown error occurred.',
            'Any changes will be lost if you leave this page.',
            'Apply this to the {number} remaining conflicts?',
            'Are you sure you want to delete this draft?',
            'Are you sure you want to delete this image?',
            'Are you sure you want to delete “{name}”?',
            'Are you sure you want to transfer your license to this domain?',
            'Buy {name}',
            'by {creator}',
            'Cancel',
            'Choose a user',
            'Choose which table columns should be visible for this source, and in which order.',
            'Close Live Preview',
            'Close',
            'Continue',
            'Could not create a Live Preview token.',
            'Couldn’t delete “{name}”.',
            'Couldn’t save new order.',
            'Create',
            'day',
            'days',
            'Delete folder',
            'Delete heading',
            'Delete it',
            'Delete user',
            'Delete users',
            'Delete',
            'Display as thumbnails',
            'Display in a table',
            'Done',
            'Draft Name',
            'Drafts',
            'Edit',
            'Edit draft settings',
            'Element',
            'Elements',
            'Enter the name of the folder',
            'Enter your password to continue.',
            'Enter your password to log back in.',
            'Export',
            'Export…',
            'Failed',
            'Format',
            'Give your tab a name.',
            'Handle',
            'Heading',
            'Hide sidebar',
            'Hide',
            'hour',
            'hours',
            'Incorrect password.',
            'Instructions',
            'Keep both',
            'Keep me logged in',
            'License transferred.',
            'Limit',
            'Log out now',
            'Login',
            'Make not required',
            'Make required',
            'Merge the folder (any conflicting files will be replaced)',
            'minute',
            'minutes',
            'More',
            'Move',
            'Name',
            'New category',
            'New child',
            'New entry',
            'New heading',
            'New order saved.',
            'New position saved.',
            'New subfolder',
            'New {group} category',
            'New {section} entry',
            'Next Page',
            'No limit',
            'Notes',
            'OK',
            'Options',
            'Password',
            'Pay {price}',
            'Pending',
            'Previous Page',
            'Really delete folder “{folder}”?',
            'Remove',
            'Rename folder',
            'Rename',
            'Reorder',
            'Replace it',
            'Replace the folder (all existing files will be deleted)',
            'Save as a new asset',
            'Save',
            'Saved',
            'Saving',
            'Score',
            'Search in subfolders',
            'second',
            'seconds',
            'Select transform',
            'Select',
            'Settings',
            'Show nav',
            'Show sidebar',
            'Show',
            'Show/hide children',
            'Sort by {attribute}',
            'Source settings saved',
            'Structure',
            'Submit',
            'Table Columns',
            'This can be left blank if you just want an unlabeled separator.',
            'Transfer it to:',
            'Try again',
            'Upload failed for {filename}',
            'Upload files',
            'week',
            'weeks',
            'What do you want to do with their content?',
            'What do you want to do?',
            'Your session has ended.',
            'Your session will expire in {time}.',
            '{ctrl}C to copy.',
            '{num} Available Updates',
            '“{name}” deleted.',
        ]);
    }

    private function _craftData(): array
    {
        $upToDate = Craft::$app->getIsInstalled() && !Craft::$app->getUpdates()->getIsCraftDbMigrationNeeded();
        $request = Craft::$app->getRequest();
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $sitesService = Craft::$app->getSites();
        $locale = Craft::$app->getLocale();
        $orientation = $locale->getOrientation();
        $userSession = Craft::$app->getUser();
        $currentUser = $userSession->getIdentity();
        $primarySite = $upToDate ? $sitesService->getPrimarySite() : null;

        $data = [
            'actionTrigger' => $generalConfig->actionTrigger,
            'actionUrl' => UrlHelper::actionUrl(),
            'allowUppercaseInSlug' => (bool)$generalConfig->allowUppercaseInSlug,
            'asciiCharMap' => StringHelper::asciiCharMap(true, Craft::$app->language),
            'baseCpUrl' => UrlHelper::cpUrl(),
            'baseSiteUrl' => UrlHelper::siteUrl(),
            'baseUrl' => UrlHelper::url(),
            'datepickerOptions' => $this->_datepickerOptions($locale, $currentUser, $generalConfig),
            'defaultIndexCriteria' => ['enabledForSite' => null],
            'editableCategoryGroups' => $upToDate ? $this->_editableCategoryGroups() : [],
            'edition' => Craft::$app->getEdition(),
            'fileKinds' => Assets::getFileKinds(),
            'forceConfirmUnload' => Craft::$app->getSession()->hasFlash('error'),
            'isImagick' => Craft::$app->getImages()->getIsImagick(),
            'isMultiSite' => Craft::$app->getIsMultiSite(),
            'language' => Craft::$app->language,
            'left' => $orientation === 'ltr' ? 'left' : 'right',
            'limitAutoSlugsToAscii' => (bool)$generalConfig->limitAutoSlugsToAscii,
            'maxUploadSize' => Assets::getMaxUploadSize(),
            'omitScriptNameInUrls' => (bool)$generalConfig->omitScriptNameInUrls,
            'orientation' => $orientation,
            'pageNum' => $request->getPageNum(),
            'pageTrigger' => $generalConfig->getPageTrigger(),
            'path' => $request->getPathInfo(),
            'pathParam' => $generalConfig->pathParam,
            'primarySiteId' => $primarySite ? (int)$primarySite->id : null,
            'primarySiteLanguage' => $primarySite->language ?? null,
            'Pro' => Craft::Pro,
            'publishableSections' => $upToDate && $currentUser ? $this->_publishableSections($currentUser) : [],
            'registeredAssetBundles' => ['' => ''], // force encode as JS object
            'registeredJsFiles' => ['' => ''], // force encode as JS object
            'remainingSessionTime' => !in_array($request->getSegment(1), ['updates', 'manualupdate'], true) ? $userSession->getRemainingSessionTime() : 0,
            'right' => $orientation === 'ltr' ? 'right' : 'left',
            'runQueueAutomatically' => (bool)$generalConfig->runQueueAutomatically,
            'scriptName' => $request->getScriptFile(),
            'siteId' => $upToDate ? (int)$sitesService->currentSite->id : null,
            'sites' => $this->_sites($sitesService),
            'slugWordSeparator' => $generalConfig->slugWordSeparator,
            'Solo' => Craft::Solo,
            'systemUid' => Craft::$app->getSystemUid(),
            'timepickerOptions' => $this->_timepickerOptions($locale, $orientation),
            'timezone' => Craft::$app->getTimeZone(),
            'tokenParam' => $generalConfig->tokenParam,
            'translations' => ['' => ''], // force encode as JS object
            'useCompressedJs' => (bool)$generalConfig->useCompressedJs,
            'usePathInfo' => (bool)$generalConfig->usePathInfo,
            'username' => $currentUser->username ?? null,
        ];

        if ($generalConfig->enableCsrfProtection) {
            $data['csrfTokenValue'] = $request->getCsrfToken();
            $data['csrfTokenName'] = $generalConfig->csrfTokenName;
        }

        return $data;
    }

    private function _datepickerOptions(Locale $locale, User $currentUser = null, GeneralConfig $generalConfig): array
    {
        return [
            'constrainInput' => false,
            'dateFormat' => $locale->getDateFormat(Locale::LENGTH_SHORT, Locale::FORMAT_JUI),
            'dayNames' => $locale->getWeekDayNames(Locale::LENGTH_FULL),
            'dayNamesMin' => $locale->getWeekDayNames(Locale::LENGTH_ABBREVIATED),
            'dayNamesShort' => $locale->getWeekDayNames(Locale::LENGTH_SHORT),
            'firstDay' => ($currentUser ? $currentUser->getPreference('weekStartDay') : null) ?: $generalConfig->defaultWeekStartDay,
            'monthNames' => $locale->getMonthNames(Locale::LENGTH_FULL),
            'monthNamesShort' => $locale->getMonthNames(Locale::LENGTH_ABBREVIATED),
            'nextText' => Craft::t('app', 'Next'),
            'prevText' => Craft::t('app', 'Prev'),
        ];
    }

    private function _editableCategoryGroups(): array
    {
        $groups = [];

        foreach (Craft::$app->getCategories()->getEditableGroups() as $group) {
            $groups[] = [
                'handle' => $group->handle,
                'id' => (int)$group->id,
                'name' => Craft::t('site', $group->name),
                'uid' => Craft::t('site', $group->uid),
            ];
        }

        return $groups;
    }

    private function _publishableSections(User $currentUser): array
    {
        $sections = [];

        foreach (Craft::$app->getSections()->getEditableSections() as $section) {
            if ($section->type !== Section::TYPE_SINGLE && $currentUser->can('createEntries:' . $section->uid)) {
                $sections[] = [
                    'entryTypes' => $this->_entryTypes($section),
                    'handle' => $section->handle,
                    'id' => (int)$section->id,
                    'name' => Craft::t('site', $section->name),
                    'sites' => $section->getSiteIds(),
                    'type' => $section->type,
                    'uid' => $section->uid,
                ];
            }
        }

        return $sections;
    }

    private function _entryTypes(Section $section): array
    {
        $types = [];

        foreach ($section->getEntryTypes() as $type) {
            $types[] = [
                'handle' => $type->handle,
                'id' => (int)$type->id,
                'name' => Craft::t('site', $type->name),
            ];
        }

        return $types;
    }

    private function _sites(Sites $sitesService): array
    {
        $sites = [];

        foreach ($sitesService->getAllSites() as $site) {
            $sites[] = [
                'handle' => $site->handle,
                'id' => (int)$site->id,
                'uid' => (string)$site->uid,
                'name' => Craft::t('site', $site->name),
            ];
        }

        return $sites;
    }

    private function _timepickerOptions(Locale $locale, string $orientation): array
    {
        return [
            'closeOnWindowScroll' => false,
            'lang' => [
                'AM' => $locale->getAMName(),
                'am' => mb_strtolower($locale->getAMName()),
                'PM' => $locale->getPMName(),
                'pm' => mb_strtolower($locale->getPMName()),
            ],
            'orientation' => $orientation[0],
            'timeFormat' => $locale->getTimeFormat(Locale::LENGTH_SHORT, Locale::FORMAT_PHP),
        ];
    }
}
