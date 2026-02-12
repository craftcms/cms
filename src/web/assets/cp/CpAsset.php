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
use craft\enums\CmsEdition;
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
use craft\utilities\QueueManager;
use craft\validators\UserPasswordValidator;
use craft\web\AssetBundle;
use craft\web\assets\animationblocker\AnimationBlockerAsset;
use craft\web\assets\axios\AxiosAsset;
use craft\web\assets\d3\D3Asset;
use craft\web\assets\datepickeri18n\DatepickerI18nAsset;
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
use craft\web\assets\theme\ThemeAsset;
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
        AnimationBlockerAsset::class,
        AxiosAsset::class,
        D3Asset::class,
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
        ThemeAsset::class,
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
            'Add Group',
            'Add',
            'Add…',
            'All',
            'Announcements',
            'Another page already has that name.',
            'Any changes will be lost if you leave this page.',
            'Apply this to the {number} remaining conflicts?',
            'Apply',
            'Are you sure you want to close the editor? Any changes will be lost.',
            'Are you sure you want to close this screen? Any changes will be lost.',
            'Are you sure you want to delete the selected {type}?',
            'Are you sure you want to delete this image?',
            'Are you sure you want to delete this {type}?',
            'Are you sure you want to delete “{name}”?',
            'Are you sure you want to discard your changes?',
            'Are you sure you want to move the selected items?',
            'Are you sure you want to remove the page “{name}”?',
            'Are you sure you want to transfer your license to this domain?',
            'Are you sure you want to undo the move?',
            'Ascending',
            'Assets',
            'Attributes',
            'Back to pages',
            'Back to sources',
            'Breadcrumbs',
            'Buy {name}',
            'Cancel',
            'Change',
            'Changes saved.',
            'Check your email for instructions to reset your password.',
            'Choose a page',
            'Choose a user',
            'Choose which sites this source should be visible for.',
            'Choose which table columns should be visible for this source by default.',
            'Choose which user groups should have access to this source.',
            'Choose',
            'Clear search',
            'Clear',
            'Close Preview',
            'Close',
            'Collapse selected blocks',
            'Color hex value',
            'Color picker',
            'Content',
            'Continue',
            'Copied to clipboard.',
            'Copy URL',
            'Copy from',
            'Copy selected {type}',
            'Copy the URL',
            'Copy the reference tag',
            'Copy to clipboard',
            'Copy “{name}” value',
            'Copy',
            'Could not save due to validation errors.',
            'Couldn’t delete “{name}”.',
            'Couldn’t reorder items.',
            'Couldn’t save new order.',
            'Create {type}',
            'Create',
            'Custom',
            'Customize sources',
            'Default Sort',
            'Default Table Columns',
            'Default View Mode',
            'Delete custom source',
            'Delete folder',
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
            'Display as cards',
            'Display as thumbnails',
            'Display in a structured table',
            'Display in a table',
            'Done',
            'Draft Name',
            'Duplicate',
            'Edit draft settings',
            'Edit {type}',
            'Edit',
            'Edited',
            'Element',
            'Elements',
            'Enabled for all sites',
            'Enabled',
            'Enter the name of the folder',
            'Enter your password to log back in.',
            'Error',
            'Existing {type}',
            'Expand selected blocks',
            'Export Type',
            'Export',
            'Export…',
            'Failed',
            'Fields',
            'Folder actions',
            'Folder created.',
            'Folder created.',
            'Folder deleted.',
            'Folder deleted.',
            'Folder renamed.',
            'Folder renamed.',
            'Format',
            'Found {num, number} {num, plural, =1{error} other{errors}} in this tab.',
            'From {date}',
            'From',
            'General',
            'Give your tab a name.',
            'Group Name',
            'Handle',
            'Heading',
            'Height unit',
            'Hide sidebar',
            'Hide',
            'Incorrect password.',
            'Indexing assets: {progress}',
            'Information',
            'Instructions',
            'Invalid email.',
            'Invalid username or email.',
            'Items reordered.',
            'Keep both',
            'Keep me signed in',
            'Keep them',
            'Label',
            'Landscape',
            'Level {num}',
            'License transferred.',
            'Limit',
            'Loading complete',
            'Loading',
            'Make not required',
            'Make optional',
            'Make required',
            'Merge the folder (any conflicting files will be replaced)',
            'Missing or empty {items}',
            'Missing {items}',
            'Mobile',
            'More info',
            'More items',
            'More',
            'More…',
            'Move backward',
            'Move down',
            'Move folder',
            'Move forward',
            'Move reverted.',
            'Move to next group',
            'Move to previous group',
            'Move to the left',
            'Move to the right',
            'Move to {page}',
            'Move to',
            'Move up',
            'Move',
            'Name',
            'New category in the {group} category group',
            'New category, choose a category group',
            'New child',
            'New custom source',
            'New entry in the {section} section',
            'New entry, choose a section',
            'New field',
            'New file uploaded.',
            'New heading',
            'New order saved.',
            'New page',
            'New position saved.',
            'New position saved.',
            'New subfolder',
            'New {group} category',
            'New {section} entry',
            'New {type}',
            'Next Page',
            'No limit',
            'Notes',
            'Notice',
            'Number of columns',
            'OK',
            'Open in a new tab',
            'Options',
            'Page Name',
            'Page settings',
            'Pages',
            'Password',
            'Past year',
            'Past {num} days',
            'Paste {type}',
            'Pay {price}',
            'Pending',
            'Phone',
            'Portrait',
            'Preview file',
            'Preview',
            'Previewing {type} device in {orientation}',
            'Previewing {type} device',
            'Previous Page',
            'Process complete',
            'Processing',
            'Really delete folder “{folder}”?',
            'Recent Activity',
            'Refresh',
            'Reload',
            'Remove heading',
            'Remove page',
            'Remove {label}',
            'Remove',
            'Rename folder',
            'Rename',
            'Reorder',
            'Replace it',
            'Replace the folder (all existing files will be deleted)',
            'Replace',
            'Required',
            'Rotate',
            'Row could not be added. Maximum number of rows reached.',
            'Row could not be deleted. Minimum number of rows reached.',
            'Row {index}',
            'Save as a new {type}',
            'Save',
            'Saved {timestamp} by {creator}',
            'Saved {timestamp}',
            'Saving',
            'Score',
            'Search in subfolders',
            'Search',
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
            'Showing {first, number}-{last, number} of {total, number} {total, plural, =1{{item}} other{{items}}}',
            'Showing {total, number} {total, plural, =1{{item}} other{{items}}}',
            'Sign out now',
            'Sites',
            'Skip to card view designer',
            'Skip to top of preview',
            'Sort ascending',
            'Sort attribute',
            'Sort by',
            'Sort descending',
            'Sort direction',
            'Source settings saved',
            'Source settings',
            'Sources',
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
            'This {type} has been updated.',
            'Tip',
            'Title',
            'To {date}',
            'To',
            'Today',
            'Transfer it to:',
            'Try again',
            'Try another way',
            'Undo',
            'Unread announcements',
            'Update {type}',
            'Upload a file',
            'Upload failed for “{filename}”.',
            'Upload failed.',
            'Upload files',
            'Use defaults',
            'Use the arrow keys to change position, Tab or Spacebar to drop.',
            'User Groups',
            'View in a new tab',
            'View in a new tab',
            'View mode options',
            'View settings',
            'View',
            'Volume path',
            'Warning',
            'What do you want to do with their content?',
            'What do you want to do?',
            'Width unit',
            'You must specify a tab name.',
            'Your changes could not be stored.',
            'Your changes have been stored.',
            'Your session will expire in {time}.',
            'by {creator}',
            'day',
            'days',
            'draft',
            'element',
            'elements',
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
            '{item} dropped.',
            '{item} picked up.',
            '{name} active, more info',
            '{name} folder',
            '{name} sorted by {attribute}, {direction}',
            '{num, number} {num, plural, =1{Available Update} other{Available Updates}}',
            '{num, number} {num, plural, =1{degree} other{degrees}}',
            '{num, number} {num, plural, =1{notification} other{notifications}}',
            '{num, number} {num, plural, =1{result} other{results}}',
            '{num} percent complete',
            '{pct} width',
            '{total, number} {total, plural, =1{error} other{errors}} found in {num, number} {num, plural, =1{tab} other{tabs}}.',
            '{total, number} {total, plural, =1{{item}} other{{items}}}',
            '{total, number} {type} copied.',
            '{totalItems, plural, =1{Item} other{Items}} moved.',
            '{type} Criteria',
            '{type} copied.',
            '{type} saved.',
            '“{name}” deleted.',
        ]);

        $view->registerTranslations('yii', [
            '{attribute} cannot be blank.',
            '{attribute} should contain at least {min, number} {min, plural, one{character} other{characters}}.',
            '{attribute} should contain at most {max, number} {max, plural, one{character} other{characters}}.',
        ]);

        $view->registerIcons([
            'arrow-down',
            'arrow-left',
            'arrow-right',
            'arrow-up',
            'arrows-rotate',
            'asterisk',
            'asterisk-slash',
            'clipboard',
            'clone',
            'clone-dashed',
            'duplicate',
            'edit',
            'gear',
            'image',
            'image-slash',
            'move',
            'pencil',
            'plus',
            'remove',
            'share',
            'trash',
            'xmark',
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
            'Solo' => CmsEdition::Solo->value,
            'Team' => CmsEdition::Team->value,
            'Pro' => CmsEdition::Pro->value,
            'Enterprise' => CmsEdition::Enterprise->value,
            'actionTrigger' => $generalConfig->actionTrigger,
            'actionUrl' => UrlHelper::actionUrl(),
            'asciiCharMap' => StringHelper::asciiCharMap(true, Craft::$app->language),
            'baseApiUrl' => Craft::$app->baseApiUrl,
            'baseSiteUrl' => UrlHelper::siteUrl(),
            'baseUrl' => UrlHelper::url(),
            'clientOs' => $request->getClientOs(),
            'datepickerOptions' => $this->_datepickerOptions($formattingLocale, $locale, $currentUser, $generalConfig),
            'defaultCookieOptions' => $this->_defaultCookieOptions(),
            'fileKinds' => Assets::getFileKinds(),
            'language' => Craft::$app->language,
            'left' => $orientation === 'ltr' ? 'left' : 'right',
            'maxPasswordLength' => UserPasswordValidator::MAX_PASSWORD_LENGTH,
            'minPasswordLength' => UserPasswordValidator::MIN_PASSWORD_LENGTH,
            'omitScriptNameInUrls' => $generalConfig->omitScriptNameInUrls,
            'orientation' => $orientation,
            'pageNum' => $request->getPageNum(),
            'pageTrigger' => 'p',
            'path' => $request->getPathInfo(),
            'pathParam' => $generalConfig->pathParam,
            'registeredAssetBundles' => [], // force encode as JS object
            'registeredJsFiles' => [], // force encode as JS object
            'resourceBaseUrl' => Craft::$app->getAssetManager()->baseUrl,
            'right' => $orientation === 'ltr' ? 'right' : 'left',
            'scriptName' => basename($request->getScriptFile()),
            'systemUid' => Craft::$app->getSystemUid(),
            'timepickerOptions' => $this->_timepickerOptions($formattingLocale, $orientation),
            'timezone' => Craft::$app->getTimeZone(),
            'tokenParam' => $generalConfig->tokenParam,
            'translations' => ['' => ''], // force encode as JS object
            'useEmailAsUsername' => $generalConfig->useEmailAsUsername,
            'usePathInfo' => $generalConfig->usePathInfo,
        ];

        if ($request->getIsCpRequest()) {
            $data += [
                'announcements' => $upToDate ? Craft::$app->getAnnouncements()->get() : [],
                'baseCpUrl' => UrlHelper::cpUrl(),
                'cpTrigger' => $generalConfig->cpTrigger,
            ];
        }

        if ($generalConfig->enableCsrfProtection) {
            $data += [
                'csrfTokenName' => $request->csrfParam,
                'csrfTokenValue' => $request->getCsrfToken(),
            ];
        }

        // If no one's logged in yet, leave it at that
        if (!$currentUser) {
            return $data;
        }

        $elementTypeNames = [];
        foreach (Craft::$app->getElements()->getAllElementTypes() as $elementType) {
            /** @var class-string<ElementInterface> $elementType */
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
            'autofocusPreferred' => $currentUser->getAutofocusPreferred(),
            'autosaveDrafts' => $generalConfig->autosaveDrafts,
            'canAccessQueueManager' => Craft::$app->getUtilities()->checkAuthorization(QueueManager::class),
            'dataAttributes' => Html::$dataAttributes,
            'defaultIndexCriteria' => [],
            'disableAutofocus' => (bool)(
                $currentUser->getPreference('disableAutofocus')
                ?? $generalConfig->accessibilityDefaults['disableAutofocus']
                ?? false
            ),
            'editableCategoryGroups' => $upToDate ? $this->_editableCategoryGroups() : [],
            'edition' => Craft::$app->edition->value,
            'elementTypeNames' => $elementTypeNames,
            'elevatedSessionDuration' => $generalConfig->elevatedSessionDuration,
            'fieldsWithoutContent' => array_map(fn(FieldInterface $field) => $field->handle, Craft::$app->getFields()->getFieldsWithoutContent(false)),
            'handleCasing' => $generalConfig->handleCasing,
            'httpProxy' => $this->_httpProxy($generalConfig),
            'isImagick' => Craft::$app->getImages()->getIsImagick(),
            'isMultiSite' => Craft::$app->getIsMultiSite(),
            'limitAutoSlugsToAscii' => $generalConfig->limitAutoSlugsToAscii,
            'maxUploadSize' => Assets::getMaxUploadSize(),
            'notificationDuration' => (int)(
                $currentUser->getPreference('notificationDuration')
                ?? $generalConfig->accessibilityDefaults['notificationDuration']
                ?? 5000
            ),
            'notificationPosition' => $currentUser->getPreference('notificationPosition')
                ?? $generalConfig->accessibilityDefaults['notificationPosition']
                ?? 'end-start',
            'slideoutPosition' => $currentUser->getPreference('slideoutPosition')
                ?? $generalConfig->accessibilityDefaults['slideoutPosition']
                ?? 'end',
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
            'userEmail' => $currentUser->email,
            'userHasPasskeys' => Craft::$app->getAuth()->hasPasskeys($userSession->getImpersonator() ?? $currentUser),
            'userId' => $currentUser->id,
            'userIsAdmin' => $currentUser->admin,
            'username' => $currentUser->username,
        ];

        return $data;
    }

    private function _datepickerOptions(Locale $formattingLocale, Locale $locale, ?User $currentUser, GeneralConfig $generalConfig): array
    {
        return [
            'constrainInput' => false,
            'changeYear' => true,
            'dateFormat' => $formattingLocale->getDateFormat(Locale::LENGTH_SHORT, Locale::FORMAT_JUI),
            'dayNames' => $locale->getWeekDayNames(Locale::LENGTH_FULL),
            'dayNamesMin' => $locale->getWeekDayNames(Locale::LENGTH_ABBREVIATED),
            'dayNamesShort' => $locale->getWeekDayNames(Locale::LENGTH_SHORT),
            'firstDay' => DateTimeHelper::firstWeekDay(),
            'monthNames' => $locale->getMonthNames(Locale::LENGTH_FULL),
            'monthNamesShort' => $locale->getMonthNames(Locale::LENGTH_ABBREVIATED),
            'nextText' => Craft::t('app', 'Next'),
            'prevText' => Craft::t('app', 'Prev'),
            'yearRange' => 'c-100:c+100',
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

        foreach (Craft::$app->getEntries()->getEditableSections() as $section) {
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
        // normalize the AM/PM names consistently with time2int() in jQuery Timepicker
        $am = preg_replace('/[\s.]/', '', $formattingLocale->getAMName());
        $pm = preg_replace('/[\s.]/', '', $formattingLocale->getPMName());

        return [
            'closeOnWindowScroll' => false,
            'lang' => [
                'AM' => $am,
                'am' => mb_strtolower($am),
                'PM' => $pm,
                'pm' => mb_strtolower($pm),
            ],
            'orientation' => $orientation[0],
            'timeFormat' => $formattingLocale->getTimeFormat(Locale::LENGTH_SHORT, Locale::FORMAT_PHP),
        ];
    }
}
