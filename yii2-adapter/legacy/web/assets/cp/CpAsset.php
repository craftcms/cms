<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\cp;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Assets;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
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
use CraftCms\Cms\Announcement\Announcements;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Images;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Translation\Locale;
use CraftCms\Cms\Updates\Updates;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\QueueManager;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Yii2Adapter\Yii2ServiceProvider;
use Illuminate\Support\Facades\Auth;
use stdClass;
use yii\web\JqueryAsset;
use function CraftCms\Cms\t;

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
        SelectizeAsset::class,
        VelocityAsset::class,
        FileUploadAsset::class,
        XregexpAsset::class,
        FabricAsset::class,
        IframeResizerAsset::class,
        ThemeAsset::class,
        PicturefillAsset::class,
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
            $this->_registerIcons();
        }

        // Define the Craft object
        $craftJson = Json::encode($this->_craftData());
        $js = <<<JS
window.Craft = $craftJson;
JS;
        HtmlStack::js($js, Position::Head);
    }

    private function _registerIcons(): void
    {
        HtmlStack::icons([
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
        $upToDate = Cms::isInstalled() && !app(Updates::class)->areMigrationsPending();
        $request = Craft::$app->getRequest();
        $generalConfig = Cms::config();
        $formattingLocale = I18N::getFormattingLocale();
        $locale = I18N::getLocale();
        $orientation = $locale->getOrientation();
        $userSession = Craft::$app->getUser();
        $currentUser = Auth::user();
        $primarySite = $upToDate ? Sites::getPrimarySite() : null;

        $data = [
            'Solo' => Edition::Solo->value,
            'Team' => Edition::Team->value,
            'Pro' => Edition::Pro->value,
            'Enterprise' => Edition::Enterprise->value,
            'actionTrigger' => $generalConfig->actionTrigger,
            'actionUrl' => UrlHelper::actionUrl(),
            'asciiCharMap' => Str::asciiCharMap(true, app()->getLocale()),
            'baseApiUrl' => Api::craftApiEndpoint(),
            'baseSiteUrl' => UrlHelper::siteUrl(),
            'baseUrl' => UrlHelper::url(),
            'clientOs' => $request->getClientOs(),
            'datepickerOptions' => $this->_datepickerOptions($formattingLocale, $locale, $currentUser, $generalConfig),
            'defaultCookieOptions' => $this->_defaultCookieOptions(),
            'fileKinds' => Assets::getFileKinds(),
            'language' => app()->getLocale(),
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
            'timezone' => app()->getTimezone(),
            'tokenParam' => $generalConfig->tokenParam,
            'translations' => I18N::getAllTranslationsForLocale(app()->getLocale()) ?: new stdClass(),
            'useEmailAsUsername' => $generalConfig->useEmailAsUsername,
            'usePathInfo' => $generalConfig->usePathInfo,
        ];

        if ($request->getIsCpRequest()) {
            $data += [
                'announcements' => $upToDate ? app(Announcements::class)->get() : [],
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
            'apiParams' => app(Api::class)->apiParams,
            'appId' => Craft::$app->id,
            'autofocusPreferred' => $currentUser->getAutofocusPreferred(),
            'autosaveDrafts' => $generalConfig->autosaveDrafts,
            'canAccessQueueManager' => app(Utilities::class)->checkAuthorization(QueueManager::class),
            'dataAttributes' => Html::$dataAttributes,
            'defaultIndexCriteria' => [],
            'disableAutofocus' => (bool)(
                $currentUser->getPreference('disableAutofocus')
                ?? $generalConfig->accessibilityDefaults['disableAutofocus']
                ?? false
            ),
            'edition' => Edition::get()->value,
            'elementTypeNames' => $elementTypeNames,
            'elevatedSessionDuration' => $generalConfig->elevatedSessionDuration,
            'fieldsWithoutContent' => app(Fields::class)->getFieldsWithoutContent(false)->pluck('handle')->all(),
            'handleCasing' => $generalConfig->handleCasing,
            'httpProxy' => $this->_httpProxy($generalConfig),
            'isImagick' => Images::getIsImagick(),
            'isMultiSite' => Sites::isMultiSite(),
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
            'primarySiteLanguage' => $primarySite->getLanguage(),
            'publishableSections' => $upToDate ? $this->_publishableSections($currentUser) : [],
            'remainingSessionTime' => !in_array($request->getSegment(1), ['updates', 'manualupdate'], true) ? $userSession->getRemainingSessionTime() : 0,
            'runQueueAutomatically' => $generalConfig->runQueueAutomatically,
            'siteId' => $upToDate ? (Cp::requestedSite()->id ?? Sites::getCurrentSite()->id) : null,
            'sites' => $this->_sites(),
            'siteToken' => $generalConfig->siteToken,
            'slugWordSeparator' => $generalConfig->slugWordSeparator,
            'userEmail' => $currentUser->email,
            'userHasPasskeys' => app(Passkeys::class)->hasPasskeys(app(Impersonation::class)->getImpersonator() ?? $currentUser),
            'userId' => $currentUser->id,
            'userIsAdmin' => $currentUser->admin,
            'username' => $currentUser->username,

            // deprecated
            'editableCategoryGroups' => $upToDate ? $this->_editableCategoryGroups() : [],
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
            'nextText' => t('Next'),
            'prevText' => t('Prev'),
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

        if (!Yii2ServiceProvider::supportsCategories()) {
            return $groups;
        }

        foreach (Craft::$app->getCategories()->getEditableGroups() as $group) {
            $groups[] = [
                'handle' => $group->handle,
                'id' => (int)$group->id,
                'name' => t($group->name, category: 'site'),
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

        foreach (Sections::getEditableSections() as $section) {
            if ($section->type !== SectionType::Single && $currentUser->can("createEntries:$section->uid")) {
                $sections[] = [
                    'entryTypes' => $this->_entryTypes($section),
                    'handle' => $section->handle,
                    'id' => (int)$section->id,
                    'name' => t($section->name, category: 'site'),
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
                'name' => t($type->name, category: 'site'),
            ];
        }

        return $types;
    }

    private function _sites(): array
    {
        $sites = [];

        foreach (Sites::getAllSites() as $site) {
            $sites[] = [
                'handle' => $site->handle,
                'id' => (int)$site->id,
                'uid' => (string)$site->uid,
                'name' => t($site->getName(), category: 'site'),
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
