<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use Craft;
use craft\validators\UserPasswordValidator;
use CraftCms\Cms\Announcement\Announcements;
use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\RequestedSite;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Images;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Translation\Locale;
use CraftCms\Cms\Update\Updates;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\QueueManager;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Facades\Auth;
use stdClass;
use Yiisoft\Aliases\Aliases as YiiAliases;

use function CraftCms\Cms\t;

#[Scoped]
readonly class CpBootstrap
{
    public function __construct(
        private Announcements $announcements,
        private Api $api,
        private Elements $elements,
        private Fields $fields,
        private Passkeys $passkeys,
        private RequestedSite $requestedSite,
        private Updates $updates,
        private Utilities $utilities,
        private YiiAliases $aliases,
    ) {}

    /**
     * @return list<string>
     */
    public function icons(): array
    {
        return [
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
        ];
    }

    /**
     * @param  list<string>  $registeredBundleIds
     * @param  list<string>  $registeredJsFiles
     * @return array<string, mixed>
     */
    public function craftData(array $registeredBundleIds, array $registeredJsFiles): array
    {
        $upToDate = Cms::isInstalled() && ! $this->updates->areMigrationsPending();
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
            'actionUrl' => Url::actionUrl(),
            'asciiCharMap' => Str::asciiCharMap(true, app()->getLocale()),
            'baseApiUrl' => Api::craftApiEndpoint(),
            'baseSiteUrl' => Url::siteUrl(),
            'baseUrl' => Url::url(),
            'clientOs' => $request->getClientOs(),
            'datepickerOptions' => $this->datepickerOptions($formattingLocale, $locale),
            'defaultCookieOptions' => $this->defaultCookieOptions(),
            'fileKinds' => AssetsHelper::getFileKinds(),
            'language' => app()->getLocale(),
            'left' => $orientation === 'ltr' ? 'left' : 'right',
            'maxPasswordLength' => UserPasswordValidator::MAX_PASSWORD_LENGTH,
            'minPasswordLength' => UserPasswordValidator::MIN_PASSWORD_LENGTH,
            'omitScriptNameInUrls' => $generalConfig->omitScriptNameInUrls,
            'orientation' => $orientation,
            'pageNum' => $request->getPageNum(),
            'pageTrigger' => $generalConfig->getPageTriggerParam(),
            'path' => $request->getPathInfo(),
            'pathParam' => $generalConfig->pathParam,
            'registeredAssetBundles' => array_values($registeredBundleIds),
            'registeredJsFiles' => array_values($registeredJsFiles),
            'resourceBaseUrl' => $this->aliases->get($generalConfig->resourceBaseUrl),
            'right' => $orientation === 'ltr' ? 'right' : 'left',
            'scriptName' => basename((string) $request->getScriptFile()),
            'systemUid' => Cms::systemUid(),
            'timepickerOptions' => $this->timepickerOptions($formattingLocale, $orientation),
            'timezone' => Cms::timezone(),
            'tokenParam' => $generalConfig->tokenParam,
            'translations' => I18N::getAllTranslationsForLocale(app()->getLocale()) ?: new stdClass,
            'useEmailAsUsername' => $generalConfig->useEmailAsUsername,
            'usePathInfo' => $generalConfig->usePathInfo,
        ];

        if ($request->getIsCpRequest()) {
            $data += [
                'announcements' => $upToDate ? $this->announcements->get() : [],
                'baseCpUrl' => Url::cpUrl(),
                'cpTrigger' => $generalConfig->cpTrigger,
            ];
        }

        if ($generalConfig->enableCsrfProtection) {
            $data += [
                'csrfTokenName' => $request->csrfParam,
                'csrfTokenValue' => $request->getCsrfToken(),
            ];
        }

        if (! $currentUser) {
            return $data;
        }

        $elementTypeNames = [];
        foreach ($this->elements->getAllElementTypes() as $elementType) {
            $elementTypeNames[$elementType] = [
                $elementType::displayName(),
                $elementType::pluralDisplayName(),
                $elementType::lowerDisplayName(),
                $elementType::pluralLowerDisplayName(),
            ];
        }

        return $data + [
            'allowAdminChanges' => $generalConfig->allowAdminChanges,
            'allowUpdates' => $generalConfig->allowUpdates,
            'allowUppercaseInSlug' => $generalConfig->allowUppercaseInSlug,
            'apiParams' => $this->api->apiParams,
            'appId' => 'CraftCMS',
            'autofocusPreferred' => $currentUser->getAutofocusPreferred(),
            'autosaveDrafts' => $generalConfig->autosaveDrafts,
            'canAccessQueueManager' => $this->utilities->checkAuthorization(QueueManager::class),
            'dataAttributes' => Html::$dataAttributes,
            'defaultIndexCriteria' => [],
            'disableAutofocus' => (bool) (
                $currentUser->getPreference('disableAutofocus')
                ?? $generalConfig->accessibilityDefaults['disableAutofocus']
                ?? false
            ),
            'editableCategoryGroups' => [],
            'edition' => Edition::get()->value,
            'elementTypeNames' => $elementTypeNames,
            'elevatedSessionDuration' => $generalConfig->elevatedSessionDuration,
            'fieldsWithoutContent' => $this->fields->getFieldsWithoutContent(false)->pluck('handle')->all(),
            'handleCasing' => $generalConfig->handleCasing,
            'httpProxy' => $this->httpProxy($generalConfig),
            'isImagick' => Images::getIsImagick(),
            'isMultiSite' => Sites::isMultiSite(),
            'limitAutoSlugsToAscii' => $generalConfig->limitAutoSlugsToAscii,
            'maxUploadSize' => AssetsHelper::getMaxUploadSize(),
            'notificationDuration' => (int) (
                $currentUser->getPreference('notificationDuration')
                ?? $generalConfig->accessibilityDefaults['notificationDuration']
                ?? 5000
            ),
            'notificationPosition' => $currentUser->getPreference('notificationPosition')
                ?? $generalConfig->accessibilityDefaults['notificationPosition']
                ?? 'end-start',
            'previewIframeResizerOptions' => $this->previewIframeResizerOptions($generalConfig),
            'primarySiteId' => $primarySite?->id,
            'primarySiteLanguage' => $primarySite?->getLanguage(),
            'publishableSections' => $upToDate ? $this->publishableSections($currentUser) : [],
            'remainingSessionTime' => ! in_array($request->getSegment(1), ['updates', 'manualupdate'], true)
                ? $userSession->getRemainingSessionTime()
                : 0,
            'runQueueAutomatically' => $generalConfig->runQueueAutomatically,
            'siteId' => $upToDate ? ($this->requestedSite->get()->id ?? Sites::getCurrentSite()->id) : null,
            'sites' => $this->sites(),
            'siteToken' => $generalConfig->siteToken,
            'slideoutPosition' => $currentUser->getPreference('slideoutPosition')
                ?? $generalConfig->accessibilityDefaults['slideoutPosition']
                ?? 'end',
            'slugWordSeparator' => $generalConfig->slugWordSeparator,
            'userEmail' => $currentUser->email,
            'userHasPasskeys' => $this->passkeys->hasPasskeys(app(Impersonation::class)->getImpersonator() ?? $currentUser),
            'userId' => $currentUser->id,
            'userIsAdmin' => $currentUser->admin,
            'username' => $currentUser->username,
        ];
    }

    private function datepickerOptions(Locale $formattingLocale, Locale $locale): array
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

    private function defaultCookieOptions(): array
    {
        $config = Craft::cookieConfig();

        return [
            'path' => $config['path'] ?? '/',
            'domain' => $config['domain'] ?? null,
            'secure' => $config['secure'] ?? false,
            'sameSite' => $config['sameSite'] ?? 'strict',
        ];
    }

    private function httpProxy(GeneralConfig $generalConfig): ?array
    {
        if (! $generalConfig->httpProxy) {
            return null;
        }

        $parsed = parse_url($generalConfig->httpProxy);

        return array_filter([
            'host' => $parsed['host'] ?? null,
            'port' => ($parsed['port'] ?? null) ?: (strtolower($parsed['scheme'] ?? 'http') === 'http' ? 80 : 443),
            'auth' => array_filter([
                'username' => $parsed['user'] ?? null,
                'password' => $parsed['pass'] ?? null,
            ]),
            'protocol' => $parsed['scheme'] ?? null,
        ]);
    }

    private function previewIframeResizerOptions(GeneralConfig $generalConfig): array|null|false
    {
        if (! $generalConfig->useIframeResizer) {
            return false;
        }

        if (empty($generalConfig->previewIframeResizerOptions)) {
            return null;
        }

        return $generalConfig->previewIframeResizerOptions;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function publishableSections(User $currentUser): array
    {
        $sections = [];

        foreach (Sections::getEditableSections() as $section) {
            if ($section->type !== SectionType::Single && $currentUser->can("createEntries:$section->uid")) {
                $sections[] = [
                    'entryTypes' => $this->entryTypes($section),
                    'handle' => $section->handle,
                    'id' => (int) $section->id,
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function entryTypes(Section $section): array
    {
        $types = [];

        foreach ($section->getEntryTypes() as $type) {
            $types[] = [
                'handle' => $type->handle,
                'id' => (int) $type->id,
                'name' => t($type->name, category: 'site'),
            ];
        }

        return $types;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sites(): array
    {
        $sites = [];

        foreach (Sites::getAllSites() as $site) {
            $sites[] = [
                'handle' => $site->handle,
                'id' => (int) $site->id,
                'uid' => (string) $site->uid,
                'name' => t($site->getName(), category: 'site'),
            ];
        }

        return $sites;
    }

    private function timepickerOptions(Locale $formattingLocale, string $orientation): array
    {
        $am = preg_replace('/[\s.]/', '', $formattingLocale->getAMName());
        $pm = preg_replace('/[\s.]/', '', $formattingLocale->getPMName());

        return [
            'closeOnWindowScroll' => false,
            'lang' => [
                'AM' => $am,
                'am' => mb_strtolower((string) $am),
                'PM' => $pm,
                'pm' => mb_strtolower((string) $pm),
            ],
            'orientation' => $orientation[0],
            'timeFormat' => $formattingLocale->getTimeFormat(Locale::LENGTH_SHORT, Locale::FORMAT_PHP),
        ];
    }
}
