<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\cp;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\config\GeneralConfig;
use craft\elements\User;
use craft\helpers\Assets;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\i18n\Locale;
use craft\models\Section;
use craft\services\Sites;
use craft\web\AssetBundle;
use craft\web\assets\axios\AxiosAsset;
use craft\web\assets\d3\D3Asset;
use craft\web\assets\datepickeri18n\DatepickerI18nAsset;
use craft\web\assets\elementresizedetector\ElementResizeDetectorAsset;
use craft\web\assets\fabric\FabricAsset;
use craft\web\assets\fileupload\FileUploadAsset;
use craft\web\assets\garnish\GarnishAsset;
use craft\web\assets\iframeresizer\IframeResizerAsset;
use craft\web\assets\jquerypayment\JqueryPaymentAsset;
use craft\web\assets\jquerytouchevents\JqueryTouchEventsAsset;
use craft\web\assets\jqueryui\JqueryUiAsset;
use craft\web\assets\picturefill\PicturefillAsset;
use craft\web\assets\selectize\SelectizeAsset;
use craft\web\assets\tailwindreset\TailwindResetAsset;
use craft\web\assets\velocity\VelocityAsset;
use craft\web\assets\xregexp\XregexpAsset;
use craft\web\View;
use yii\web\JqueryAsset;

/**
 * Asset bundle for the control panel
 */
class CpAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $depends = [
        TailwindResetAsset::class,
        AxiosAsset::class,
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
        IframeResizerAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public $css = [
        'css/cp.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'cp.js',
    ];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $this->_registerTranslations($view);
        }

        // Define the Craft object
        $craftJson = Json::encode($this->_craftData());
        $js = <<<JS
window.Craft = $craftJson;
JS;
        $view->registerJs($js, View::POS_HEAD);
    }

    /**
     * @param View $view
     */
    private function _registerTranslations(View $view): void
    {
        $view->registerTranslations('app', [
            '(blank)',
            '<span class="visually-hidden">Characters left:</span> {chars, number}',
            'A server error occurred.',
            'Actions',
            'Add…',
            'All',
            'Announcements',
            'Any changes will be lost if you leave this page.',
            'Apply this to the {number} remaining conflicts?',
            'Apply',
            'Are you sure you want to close the editor? Any changes will be lost.',
            'Are you sure you want to close this screen? Any changes will be lost.',
            'Are you sure you want to delete this address?',
            'Are you sure you want to delete this image?',
            'Are you sure you want to delete “{name}”?',
            'Are you sure you want to discard your changes?',
            'Are you sure you want to transfer your license to this domain?',
            'Ascending',
            'Assets',
            'Breadcrumbs',
            'Buy {name}',
            'Cancel',
            'Choose a user',
            'Choose which table columns should be visible for this source by default.',
            'Choose which user groups should have access to this source.',
            'Clear',
            'Close Preview',
            'Close',
            'Color hex value',
            'Color picker',
            'Continue',
            'Copied to clipboard.',
            'Copy the URL',
            'Copy the reference tag',
            'Copy to clipboard',
            'Couldn’t delete “{name}”.',
            'Couldn’t save new order.',
            'Create',
            'Customize sources',
            'Default Sort',
            'Default Table Columns',
            'Delete custom source',
            'Delete folder',
            'Delete heading',
            'Delete their content',
            'Delete them',
            'Delete {num, plural, =1{user} other{users}} and content',
            'Delete {num, plural, =1{user} other{users}}',
            'Delete',
            'Descending',
            'Desktop',
            'Device type',
            'Discard changes',
            'Discard',
            'Display as thumbnails',
            'Display in a structured table',
            'Display in a table',
            'Done',
            'Draft Name',
            'Edit draft settings',
            'Edit',
            'Edited',
            'Element',
            'Elements',
            'Enabled for all sites',
            'Enabled',
            'Enter the name of the folder',
            'Enter your password to continue.',
            'Enter your password to log back in.',
            'Error',
            'Export Type',
            'Export',
            'Export…',
            'Failed',
            'Folder actions',
            'Folder created.',
            'Folder created.',
            'Folder deleted.',
            'Folder deleted.',
            'Folder renamed.',
            'Folder renamed.',
            'Format',
            'From {date}',
            'From',
            'Give your tab a name.',
            'Handle',
            'Heading',
            'Height unit',
            'Hide nested sources',
            'Hide sidebar',
            'Hide',
            'Incorrect password.',
            'Information',
            'Instructions',
            'Keep both',
            'Keep me signed in',
            'Keep them',
            'Label',
            'Landscape',
            'License transferred.',
            'Limit',
            'Loading',
            'Make not required',
            'Make required',
            'Matrix block could not be added. Maximum number of blocks reached.',
            'Merge the folder (any conflicting files will be replaced)',
            'Missing or empty {items}',
            'Missing {items}',
            'Mobile',
            'More info',
            'More items',
            'More',
            'More…',
            'Move down',
            'Move folder',
            'Move to the left',
            'Move to the right',
            'Move to',
            'Move up',
            'Move',
            'Name',
            'New category in the {group} category group',
            'New category',
            'New category, choose a category group',
            'New child',
            'New custom source',
            'New entry in the {section} section',
            'New entry',
            'New entry, choose a section',
            'New heading',
            'New order saved.',
            'New position saved.',
            'New subfolder',
            'New {group} category',
            'New {section} entry',
            'Next Page',
            'No limit',
            'Notes',
            'Notice',
            'OK',
            'Open the full edit page in a new tab',
            'Options',
            'Password',
            'Past year',
            'Past {num} days',
            'Pay {price}',
            'Pending',
            'Phone',
            'Portrait',
            'Preview file',
            'Preview',
            'Previewing {type} device in {orientation}',
            'Previewing {type} device',
            'Previous Page',
            'Really delete folder “{folder}”?',
            'Refresh',
            'Remove {label}',
            'Remove',
            'Rename folder',
            'Rename',
            'Reorder',
            'Replace it',
            'Replace the folder (all existing files will be deleted)',
            'Rotate',
            'Row could not be added. Maximum number of rows reached.',
            'Row could not be deleted. Minimum number of rows reached.',
            'Save as a new asset',
            'Save',
            'Saved {timestamp} by {creator}',
            'Saved {timestamp}',
            'Saving',
            'Score',
            'Search in subfolders',
            'Select all',
            'Select element',
            'Select transform',
            'Select {element}',
            'Select',
            'Settings',
            'Show nav',
            'Show nested sources',
            'Show sidebar',
            'Show {title} children',
            'Show',
            'Show/hide children',
            'Showing your unsaved changes.',
            'Sign in',
            'Sign out now',
            'Skip to {title}',
            'Sort ascending',
            'Sort attribute',
            'Sort by',
            'Sort descending',
            'Sort direction',
            'Source settings saved',
            'Source settings',
            'Source',
            'Structure',
            'Submit',
            'Success',
            'Switching sites will lose unsaved changes. Are you sure you want to switch sites?',
            'Table Columns',
            'Tablet',
            'The draft could not be saved.',
            'The draft has been saved.',
            'The following {items} could not be found or are empty. Should they be deleted from the index?',
            'The following {items} could not be found. Should they be deleted from the index?',
            'This can be left blank if you just want an unlabeled separator.',
            'This field has been modified.',
            'This month',
            'This tab is conditional',
            'This week',
            'This year',
            'Tip',
            'Title',
            'To {date}',
            'To',
            'Today',
            'Top of preview',
            'Transfer it to:',
            'Try again',
            'Undo',
            'Unread announcements',
            'Update {type}',
            'Upload a file',
            'Upload failed for “{filename}”.',
            'Upload failed.',
            'Upload files',
            'Use defaults',
            'User Groups',
            'View',
            'Volume path',
            'Warning',
            'What do you want to do with their content?',
            'What do you want to do?',
            'Width unit',
            'You must specify a tab name.',
            'Your changes could not be stored.',
            'Your changes have been stored.',
            'Your session has ended.',
            'Your session will expire in {time}.',
            'by {creator}',
            'day',
            'days',
            'files',
            'folders',
            'hour',
            'hours',
            'minute',
            'minutes',
            'second',
            'seconds',
            'week',
            'weeks',
            '{ctrl}C to copy.',
            '{element} pagination',
            '{first, number}-{last, number} of {total, number} {total, plural, =1{{item}} other{{items}}}',
            '{first}-{last} of {total}',
            '{name} folder',
            '{num, number} {num, plural, =1{Available Update} other{Available Updates}}',
            '{num, number} {num, plural, =1{degree} other{degrees}}',
            '{num, number} {num, plural, =1{notification} other{notifications}}',
            '{total, number} {total, plural, =1{{item}} other{{items}}}',
            '{totalItems, plural, =1{Item} other{Items}} moved.',
            '{type} Criteria',
            '{type} saved.',
            '“{name}” deleted.',
        ]);
    }

    private function _craftData(): array
    {
        $upToDate = Craft::$app->getIsInstalled() && !Craft::$app->getUpdates()->getAreMigrationsPending();
        $request = Craft::$app->getRequest();
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $sitesService = Craft::$app->getSites();
        $formattingLocale = Craft::$app->getFormattingLocale();
        $locale = Craft::$app->getLocale();
        $orientation = $locale->getOrientation();
        $userSession = Craft::$app->getUser();
        $currentUser = $userSession->getIdentity();
        $primarySite = $upToDate ? $sitesService->getPrimarySite() : null;

        $data = [
            'actionTrigger' => $generalConfig->actionTrigger,
            'actionUrl' => UrlHelper::actionUrl(),
            'announcements' => $upToDate ? Craft::$app->getAnnouncements()->get() : [],
            'asciiCharMap' => StringHelper::asciiCharMap(true, Craft::$app->language),
            'baseApiUrl' => Craft::$app->baseApiUrl,
            'baseCpUrl' => UrlHelper::cpUrl(),
            'baseSiteUrl' => UrlHelper::siteUrl(),
            'baseUrl' => UrlHelper::url(),
            'clientOs' => $request->getClientOs(),
            'cpTrigger' => $generalConfig->cpTrigger,
            'datepickerOptions' => $this->_datepickerOptions($formattingLocale, $locale, $currentUser, $generalConfig),
            'defaultCookieOptions' => $this->_defaultCookieOptions(),
            'fileKinds' => Assets::getFileKinds(),
            'language' => Craft::$app->language,
            'left' => $orientation === 'ltr' ? 'left' : 'right',
            'omitScriptNameInUrls' => $generalConfig->omitScriptNameInUrls,
            'orientation' => $orientation,
            'pageNum' => $request->getPageNum(),
            'pageTrigger' => 'p',
            'path' => $request->getPathInfo(),
            'pathParam' => $generalConfig->pathParam,
            'Pro' => Craft::Pro,
            'registeredAssetBundles' => ['' => ''], // force encode as JS object
            'registeredJsFiles' => ['' => ''], // force encode as JS object
            'right' => $orientation === 'ltr' ? 'right' : 'left',
            'scriptName' => basename($request->getScriptFile()),
            'Solo' => Craft::Solo,
            'systemUid' => Craft::$app->getSystemUid(),
            'timepickerOptions' => $this->_timepickerOptions($formattingLocale, $orientation),
            'timezone' => Craft::$app->getTimeZone(),
            'tokenParam' => $generalConfig->tokenParam,
            'translations' => ['' => ''], // force encode as JS object
            'usePathInfo' => $generalConfig->usePathInfo,
        ];

        if ($generalConfig->enableCsrfProtection) {
            $data['csrfTokenName'] = $request->csrfParam;
            $data['csrfTokenValue'] = $request->getCsrfToken();
        }

        // If no one's logged in yet, leave it at that
        if (!$currentUser) {
            return $data;
        }

        $elementTypeNames = [];
        foreach (Craft::$app->getElements()->getAllElementTypes() as $elementType) {
            /** @var string|ElementInterface $elementType */
            /** @phpstan-var class-string<ElementInterface>|ElementInterface $elementType */
            $elementTypeNames[$elementType] = [
                $elementType::displayName(),
                $elementType::pluralDisplayName(),
                $elementType::lowerDisplayName(),
                $elementType::pluralLowerDisplayName(),
            ];
        }

        $data += [
            'allowAdminChanges' => $generalConfig->allowAdminChanges,
            'allowUpdates' => $generalConfig->allowUpdates,
            'allowUppercaseInSlug' => $generalConfig->allowUppercaseInSlug,
            'apiParams' => Craft::$app->apiParams,
            'appId' => Craft::$app->id,
            'autosaveDrafts' => $generalConfig->autosaveDrafts,
            'canAccessQueueManager' => $userSession->checkPermission('utility:queue-manager'),
            'dataAttributes' => Html::$dataAttributes,
            'defaultIndexCriteria' => [],
            'editableCategoryGroups' => $upToDate ? $this->_editableCategoryGroups() : [],
            'edition' => Craft::$app->getEdition(),
            'elementTypeNames' => $elementTypeNames,
            'fieldsWithoutContent' => array_map(fn(FieldInterface $field) => $field->handle, Craft::$app->getFields()->getFieldsWithoutContent(false)),
            'handleCasing' => $generalConfig->handleCasing,
            'httpProxy' => $this->_httpProxy($generalConfig),
            'isImagick' => Craft::$app->getImages()->getIsImagick(),
            'isMultiSite' => Craft::$app->getIsMultiSite(),
            'limitAutoSlugsToAscii' => $generalConfig->limitAutoSlugsToAscii,
            'maxUploadSize' => Assets::getMaxUploadSize(),
            'notificationDuration' => (int)($currentUser->getPreference('notificationDuration') ?? 5000),
            'previewIframeResizerOptions' => $this->_previewIframeResizerOptions($generalConfig),
            'primarySiteId' => $primarySite ? (int)$primarySite->id : null,
            'primarySiteLanguage' => $primarySite->language ?? null,
            'publishableSections' => $upToDate ? $this->_publishableSections($currentUser) : [],
            'remainingSessionTime' => !in_array($request->getSegment(1), ['updates', 'manualupdate'], true) ? $userSession->getRemainingSessionTime() : 0,
            'runQueueAutomatically' => $generalConfig->runQueueAutomatically,
            'siteId' => $upToDate ? (Cp::requestedSite()->id ?? $sitesService->getCurrentSite()->id) : null,
            'sites' => $this->_sites($sitesService),
            'siteToken' => $generalConfig->siteToken,
            'slugWordSeparator' => $generalConfig->slugWordSeparator,
            'userIsAdmin' => $currentUser->admin,
            'username' => $currentUser->username,
        ];

        return $data;
    }

    private function _datepickerOptions(Locale $formattingLocale, Locale $locale, ?User $currentUser, GeneralConfig $generalConfig): array
    {
        return [
            'constrainInput' => false,
            'dateFormat' => $formattingLocale->getDateFormat(Locale::LENGTH_SHORT, Locale::FORMAT_JUI),
            'dayNames' => $locale->getWeekDayNames(Locale::LENGTH_FULL),
            'dayNamesMin' => $locale->getWeekDayNames(Locale::LENGTH_ABBREVIATED),
            'dayNamesShort' => $locale->getWeekDayNames(Locale::LENGTH_SHORT),
            'firstDay' => DateTimeHelper::firstWeekDay(),
            'monthNames' => $locale->getMonthNames(Locale::LENGTH_FULL),
            'monthNamesShort' => $locale->getMonthNames(Locale::LENGTH_ABBREVIATED),
            'nextText' => Craft::t('app', 'Next'),
            'prevText' => Craft::t('app', 'Prev'),
        ];
    }

    private function _defaultCookieOptions(): array
    {
        $config = Craft::cookieConfig();
        return [
            'path' => $config['path'] ?? '/',
            'domain' => $config['domain'] ?? null,
            'secure' => $config['secure'] ?? false,
            'sameSite' => $config['sameSite'] ?? 'strict',
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
                'uid' => $group->uid,
            ];
        }

        return $groups;
    }

    /**
     * @param GeneralConfig $generalConfig
     * @return array|null
     */
    private function _httpProxy(GeneralConfig $generalConfig): ?array
    {
        if (!$generalConfig->httpProxy) {
            return null;
        }

        $parsed = parse_url($generalConfig->httpProxy);

        return array_filter([
            'host' => $parsed['host'],
            'port' => $parsed['port'] ?? strtolower($parsed['scheme']) === 'http' ? 80 : 443,
            'auth' => array_filter([
                'username' => $parsed['user'] ?? null,
                'password' => $parsed['pass'] ?? null,
            ]),
            'protocol' => $parsed['scheme'],
        ]);
    }

    /**
     * @param GeneralConfig $generalConfig
     * @return array|null|false
     */
    private function _previewIframeResizerOptions(GeneralConfig $generalConfig): array|null|false
    {
        if (!$generalConfig->useIframeResizer) {
            return false;
        }

        // Treat false as [] as well now that useIframeResizer exists
        if (empty($generalConfig->previewIframeResizerOptions)) {
            return null;
        }

        return $generalConfig->previewIframeResizerOptions;
    }

    private function _publishableSections(User $currentUser): array
    {
        $sections = [];

        foreach (Craft::$app->getSections()->getEditableSections() as $section) {
            if ($section->type !== Section::TYPE_SINGLE && $currentUser->can("createEntries:$section->uid")) {
                $sections[] = [
                    'entryTypes' => $this->_entryTypes($section),
                    'handle' => $section->handle,
                    'id' => (int)$section->id,
                    'name' => Craft::t('site', $section->name),
                    'sites' => $section->getSiteIds(),
                    'type' => $section->type,
                    'uid' => $section->uid,
                    'canSave' => $currentUser->can("saveEntries:$section->uid"),
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
                'name' => Craft::t('site', $site->getName()),
            ];
        }

        return $sites;
    }

    private function _timepickerOptions(Locale $formattingLocale, string $orientation): array
    {
        return [
            'closeOnWindowScroll' => false,
            'lang' => [
                'AM' => $formattingLocale->getAMName(),
                'am' => mb_strtolower($formattingLocale->getAMName()),
                'PM' => $formattingLocale->getPMName(),
                'pm' => mb_strtolower($formattingLocale->getPMName()),
            ],
            'orientation' => $orientation[0],
            'timeFormat' => $formattingLocale->getTimeFormat(Locale::LENGTH_SHORT, Locale::FORMAT_PHP),
        ];
    }
}
