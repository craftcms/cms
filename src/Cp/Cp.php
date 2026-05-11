<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use CraftCms\Cms\Announcement\Announcements;
use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Providers\AppServiceProvider;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\Elements;
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
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use stdClass;

use function CraftCms\Cms\t;

readonly class Cp
{
    public static function config(): array
    {
        $upToDate = Cms::isInstalled() && ! app(Updates::class)->areMigrationsPending();
        $generalConfig = Cms::config();
        $formattingLocale = I18N::getFormattingLocale();
        $locale = I18N::getLocale();
        $orientation = $locale->getOrientation();
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
            'clientOs' => request()->clientOs(),
            'datepickerOptions' => self::datepickerOptions($formattingLocale, $locale),
            'defaultCookieOptions' => self::defaultCookieOptions(),
            'fileKinds' => AssetsHelper::getFileKinds(),
            'language' => app()->getLocale(),
            'left' => $orientation === 'ltr' ? 'left' : 'right',
            'maxPasswordLength' => AppServiceProvider::$maxPasswordLength,
            'minPasswordLength' => AppServiceProvider::$minPasswordLength,
            'orientation' => $orientation,
            'pageNum' => Paginator::resolveCurrentPage(Cms::config()->getPageTriggerParam()),
            'pageTrigger' => Cms::config()->getPageTriggerParam(),
            'path' => request()->craftPath(),
            'registeredAssetBundles' => [], // force encode as JS object
            'registeredJsFiles' => [], // force encode as JS object
            'right' => $orientation === 'ltr' ? 'right' : 'left',
            'systemUid' => Cms::systemUid(),
            'timepickerOptions' => self::timepickerOptions($formattingLocale, $orientation),
            'timezone' => Cms::timezone(),
            'tokenParam' => $generalConfig->tokenParam,
            'translations' => I18N::getAllTranslationsForLocale(app()->getLocale()) ?: new stdClass,
            'useEmailAsUsername' => $generalConfig->useEmailAsUsername,
        ];

        if (request()->isCpRequest()) {
            $data += [
                'announcements' => $upToDate ? app(Announcements::class)->get() : [],
                'baseCpUrl' => Url::cpUrl(),
                'cpTrigger' => $generalConfig->cpTrigger,
            ];
        }

        $data += [
            'csrfTokenName' => '_token',
            'csrfTokenValue' => csrf_token(),
        ];

        if (! $currentUser) {
            return $data;
        }

        $elementTypeNames = [];
        foreach (Elements::getAllElementTypes() as $elementType) {
            /** @var class-string<ElementInterface> $elementType */
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
            'autosaveDrafts' => true, // @TODO: This should always be true in the frontend
            'apiParams' => app(Api::class)->apiParams,
            'appId' => config('app.name'),
            'autofocusPreferred' => $currentUser->getAutofocusPreferred(),
            'canAccessQueueManager' => app(Utilities::class)->checkAuthorization(QueueManager::class),
            'dataAttributes' => Html::$dataAttributes,
            'defaultIndexCriteria' => [],
            'disableAutofocus' => (bool) (
                $currentUser->getPreference('disableAutofocus')
                ?? $generalConfig->accessibilityDefaults['disableAutofocus']
                ?? false
            ),
            'edition' => Edition::get()->value,
            'elementTypeNames' => $elementTypeNames,
            'elevatedSessionDuration' => $generalConfig->elevatedSessionDuration,
            'fieldsWithoutContent' => app(Fields::class)->getFieldsWithoutContent(false)->pluck('handle')->all(),
            'handleCasing' => $generalConfig->handleCasing,
            'httpProxy' => self::httpProxy($generalConfig),
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
            'slideoutPosition' => $currentUser->getPreference('slideoutPosition')
                ?? $generalConfig->accessibilityDefaults['slideoutPosition']
                    ?? 'end',
            'previewIframeResizerOptions' => self::previewIframeResizerOptions($generalConfig),
            'primarySiteId' => $primarySite ? (int) $primarySite->id : null,
            'primarySiteLanguage' => $primarySite?->getLanguage(),
            'publishableSections' => $upToDate ? self::publishableSections($currentUser) : [],
            'runQueueAutomatically' => $generalConfig->runQueueAutomatically,
            'siteId' => $upToDate ? (app(RequestedSite::class)->get()->id ?? Sites::getCurrentSite()->id) : null,
            'sites' => self::sites(),
            'siteToken' => $generalConfig->siteToken,
            'slugWordSeparator' => $generalConfig->slugWordSeparator,
            'userEmail' => $currentUser->email,
            'userHasPasskeys' => app(Passkeys::class)->hasPasskeys(app(Impersonation::class)->getImpersonator() ?? $currentUser),
            'userId' => $currentUser->id,
            'userIsAdmin' => $currentUser->admin,
            'username' => $currentUser->username,
        ];
    }

    private static function datepickerOptions(Locale $formattingLocale, Locale $locale): array
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

    private static function defaultCookieOptions(): array
    {
        return [
            'path' => config('session.path', '/'),
            'domain' => config('session.domain'),
            'secure' => config('session.secure', false),
            'sameSite' => config('session.same_site', 'strict'),
        ];
    }

    private static function httpProxy(GeneralConfig $generalConfig): ?array
    {
        if (! $generalConfig->httpProxy) {
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

    private static function previewIframeResizerOptions(GeneralConfig $generalConfig): array|null|false
    {
        if (! $generalConfig->useIframeResizer) {
            return false;
        }

        if (empty($generalConfig->previewIframeResizerOptions)) {
            return null;
        }

        return $generalConfig->previewIframeResizerOptions;
    }

    private static function publishableSections(User $currentUser): array
    {
        $sections = [];

        foreach (Sections::getEditableSections() as $section) {
            if ($section->type !== SectionType::Single && $currentUser->can("createEntries:$section->uid")) {
                $sections[] = [
                    'entryTypes' => self::entryTypes($section),
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

    private static function entryTypes(Section $section): array
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

    private static function sites(): array
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

    private static function timepickerOptions(Locale $formattingLocale, string $orientation): array
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
