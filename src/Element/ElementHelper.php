<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use Craft;
use craft\base\ElementInterface;
use craft\base\NestedElementInterface;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Field\Exceptions\FieldNotFoundException;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Shared\Exceptions\OperationAbortedException;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sites as SitesFacade;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tpetry\QueryExpressions\Language\Alias;
use Twig\Markup;

use function CraftCms\Cms\renderObjectTemplate;

class ElementHelper
{
    private const int URI_MAX_LENGTH = 255;

    /**
     * Generates a new temporary slug.
     */
    public static function tempSlug(): string
    {
        return '__temp_'.Str::random(36);
    }

    /**
     * Returns whether the given slug is temporary.
     */
    public static function isTempSlug(string $slug): bool
    {
        return str_starts_with($slug, '__temp_');
    }

    /**
     * Generates a new slug based on a given string.
     *
     * This is different from `normalizeSlug()` in two ways:
     *
     * - Periods and underscores will be converted to dashes, whereas `normalizeSlug()` will leave those intact.
     * - The string may be converted to ASCII.
     *
     * If `$ascii` is `null`, whether ASCII conversion happens depends on the `limitAutoSlugsToAscii`
     * config setting. `$language` is used to determine ASCII character mappings when needed.
     */
    public static function generateSlug(string $value, ?bool $ascii = null, ?string $language = null): string
    {
        $slug = str_replace(['.', '_', '-'], ' ', $value);

        if ($ascii ?? Cms::config()->limitAutoSlugsToAscii) {
            $slug = Str::ascii($slug, $language);
        }

        return self::normalizeSlug($slug);
    }

    /**
     * Normalizes a slug.
     */
    public static function normalizeSlug(string $slug): string
    {
        if ($slug === Element::HOMEPAGE_URI) {
            return $slug;
        }

        $slug = strip_tags($slug);
        $slug = preg_replace('/[\'"‘’“”ʻ\[\](){}:]/u', '', $slug);

        $generalConfig = Cms::config();

        if (! $generalConfig->allowUppercaseInSlug) {
            $slug = mb_strtolower((string) $slug);
        }

        $words = Arr::whereNotEmpty(preg_split('/[^\p{L}\p{N}\p{M}\._\-]+/u', (string) $slug));

        return implode($generalConfig->slugWordSeparator, $words);
    }

    /**
     * Sets the URI on an element using a given URL format, tweaking its slug if necessary to ensure it is unique.
     *
     * @throws OperationAbortedException if a unique URI could not be found
     */
    public static function setUniqueUri(ElementInterface $element): void
    {
        $uriFormat = $element->getUriFormat();

        if ($uriFormat === null) {
            $element->uri = null;

            return;
        }

        $testUri = self::renderUriFormat($uriFormat, $element);

        if ($testUri === '') {
            $element->uri = null;

            return;
        }

        if (! self::doesUriFormatHaveSlugTag($uriFormat)) {
            if (! self::isUniqueUri($testUri, $element)) {
                throw new OperationAbortedException('Could not find a unique URI for this element');
            }

            $element->uri = $testUri;

            return;
        }

        $generalConfig = Cms::config();
        $maxSlugIncrement = $generalConfig->maxSlugIncrement;
        $originalSlug = $element->slug ?? '';
        $originalSlugLength = mb_strlen($originalSlug);

        for ($index = 1; $index <= $maxSlugIncrement; $index++) {
            $suffix = $index !== 1 ? $generalConfig->slugWordSeparator.$index : '';
            $element->slug = $originalSlug.$suffix;
            $testUri = self::renderUriFormat($uriFormat, $element);
            $testUriLength = mb_strlen($testUri);

            if ($testUriLength > self::URI_MAX_LENGTH) {
                $overage = $testUriLength - self::URI_MAX_LENGTH;

                if ($overage >= $originalSlugLength) {
                    $element->slug = $originalSlug;

                    throw new OperationAbortedException('Could not find a unique URI for this element');
                }

                $trimmedSlug = mb_substr($originalSlug, 0, -$overage);

                if ($generalConfig->slugWordSeparator) {
                    $trimmedSlug = rtrim($trimmedSlug, $generalConfig->slugWordSeparator);
                }

                $element->slug = $trimmedSlug.$suffix;
                $testUri = self::renderUriFormat($uriFormat, $element);
            }

            if (self::isUniqueUri($testUri, $element)) {
                $element->uri = $testUri;

                return;
            }
        }

        $element->slug = $originalSlug;

        throw new OperationAbortedException('Could not find a unique URI for this element');
    }

    /**
     * Returns whether a given URL format has a proper `{slug}` tag.
     */
    public static function doesUriFormatHaveSlugTag(string $uriFormat): bool
    {
        return (bool) preg_match('/\bslug\b/', $uriFormat);
    }

    /**
     * Returns a list of sites that a given element supports.
     *
     * Each site is represented as an array with `siteId`, `propagate`, and `enabledByDefault` keys.
     *
     * @throws RuntimeException if any of the element's supported sites are invalid
     */
    public static function supportedSitesForElement(
        ElementInterface $element,
        bool $withUnpropagatedSites = false,
    ): array {
        $sites = [];
        $siteUidMap = app(Sites::class)->getAllSites(true)->pluck('uid', 'id');

        foreach ($element->getSupportedSites() as $site) {
            if (! is_array($site)) {
                $site = [
                    'siteId' => (int) $site,
                ];
            }

            if (! isset($site['siteId'])) {
                throw new RuntimeException('Missing "siteId" key in '.$element::class.'::getSupportedSites()');
            }

            $site['siteId'] = (int) $site['siteId'];

            if (! isset($siteUidMap[$site['siteId']])) {
                continue;
            }

            $site['siteUid'] = $siteUidMap[$site['siteId']];
            $site += [
                'propagate' => true,
                'enabledByDefault' => true,
            ];

            if ($withUnpropagatedSites || $site['propagate']) {
                $sites[] = $site;
            }
        }

        return $sites;
    }

    /**
     * Returns the site statuses for a given element.
     *
     * @return array<int, bool> The site statuses, indexed by site ID
     */
    public static function siteStatusesForElement(ElementInterface $element, bool $editableOnly = false): array
    {
        $supportedSites = self::supportedSitesForElement($element, true);
        $propagatedSites = array_values(array_filter($supportedSites, fn (array $site) => $site['propagate']));
        $propagatedSiteIds = array_map(fn (array $site) => $site['siteId'], $propagatedSites);

        if ($editableOnly) {
            $propagatedSiteIds = array_values(array_intersect($propagatedSiteIds, app(Sites::class)->getEditableSiteIds()->all()));
        }

        if (! $element->enabled || ! $element->id) {
            $defaultStatus = ! $element->id && $element->enabled && $element->getEnabledForSite();

            return array_combine($propagatedSiteIds, array_map(fn () => $defaultStatus, $propagatedSiteIds));
        }

        return $element::find()
            ->drafts($element->getIsDraft())
            ->provisionalDrafts($element->isProvisionalDraft)
            ->revisions($element->getIsRevision())
            ->id($element->id)
            ->siteId($propagatedSiteIds)
            ->status(null)
            ->trashed(null)
            ->asArray()
            ->select(['siteId', 'enabled'])
            ->pluck('enabled', 'siteId')
            ->map(fn ($enabled) => (bool) $enabled)
            ->all();
    }

    /**
     * Returns whether changes should be tracked for the given element.
     */
    public static function shouldTrackChanges(ElementInterface $element): bool
    {
        return $element->id &&
        $element->siteSettingsId &&
        $element->duplicateOf === null &&
        $element::trackChanges() &&
        ! $element->mergingCanonicalChanges &&
        ! $element->resaving;
    }

    /**
     * Returns whether the given element is editable by the current user, taking user permissions into account.
     */
    public static function isElementEditable(ElementInterface $element): bool
    {
        $user = Auth::user();

        if (! $user || ! $element->canView($user)) {
            return false;
        }

        if (! app(Sites::class)->isMultiSite()) {
            return true;
        }

        return array_any(self::supportedSitesForElement($element), fn ($siteInfo) => $user->can(sprintf('editSite:%s', $siteInfo['siteUid'])));
    }

    /**
     * Returns the editable site IDs for a given element, taking user permissions into account.
     *
     * @return int[]
     */
    public static function editableSiteIdsForElement(ElementInterface $element): array
    {
        $user = Auth::user();

        if (! $user || ! $element->canView($user)) {
            return [];
        }

        if (! app(Sites::class)->isMultiSite()) {
            return [app(Sites::class)->getPrimarySite()->id];
        }

        $siteIds = [];

        foreach (self::supportedSitesForElement($element) as $siteInfo) {
            if ($user->can(sprintf('editSite:%s', $siteInfo['siteUid']))) {
                $siteIds[] = $siteInfo['siteId'];
            }
        }

        return $siteIds;
    }

    /**
     * Returns whether the given element, or its root element if it is nested, is a draft.
     */
    public static function isDraft(ElementInterface $element): bool
    {
        if ($element->getIsDraft()) {
            return true;
        }

        if (! $element instanceof NestedElementInterface) {
            return false;
        }

        $owner = $element->getOwner();

        if (! $owner) {
            return false;
        }

        return self::isDraft($owner);
    }

    /**
     * Returns whether the given element, or its root element if it is nested, is a revision.
     */
    public static function isRevision(ElementInterface $element): bool
    {
        if ($element->getIsRevision()) {
            return true;
        }

        if (! $element instanceof NestedElementInterface) {
            return false;
        }

        $owner = $element->getOwner();

        if (! $owner) {
            return false;
        }

        return self::isRevision($owner);
    }

    /**
     * Returns whether the given element, or its root element if it is nested, is a draft or revision.
     */
    public static function isDraftOrRevision(ElementInterface $element): bool
    {
        if ($element->getIsDraft() || $element->getIsRevision()) {
            return true;
        }

        if (! $element instanceof NestedElementInterface) {
            return false;
        }

        $owner = $element->getOwner();

        if (! $owner) {
            return false;
        }

        return self::isDraftOrRevision($owner);
    }

    /**
     * Returns whether the given derivative element is outdated compared to its canonical element.
     */
    public static function isOutdated(ElementInterface $element): bool
    {
        if ($element->getIsCanonical()) {
            return false;
        }

        $canonical = $element->getCanonical();

        if ($element->dateCreated > $canonical->dateUpdated) {
            return false;
        }

        if (! $element->dateLastMerged) {
            return true;
        }

        return $element->dateLastMerged < $canonical->dateUpdated;
    }

    /**
     * Given an iterable of elements, sets the appropriate next and previous elements on them.
     *
     * @param  iterable<ElementInterface>  $elements
     */
    public static function setNextPrevOnElements(iterable $elements): void
    {
        $lastElement = null;

        foreach ($elements as $element) {
            if ($lastElement) {
                $lastElement->setNext($element);
                $element->setPrev($lastElement);
            } else {
                $element->setPrev(false);
            }

            $lastElement = $element;
        }

        $lastElement?->setNext(false);
    }

    /**
     * Returns whether the attribute on the given element is empty.
     */
    public static function isAttributeEmpty(ElementInterface $element, string $attribute): bool
    {
        $fieldLayout = $element->getFieldLayout();

        if (! $fieldLayout) {
            return empty($element->$attribute);
        }

        foreach ($fieldLayout->getTabs() as $tab) {
            foreach ($tab->getElements() as $layoutElement) {
                if (! $layoutElement instanceof CustomField) {
                    continue;
                }

                if ($layoutElement->attribute() !== $attribute) {
                    continue;
                }

                try {
                    $field = $layoutElement->getField();
                } catch (FieldNotFoundException) {
                    continue;
                }

                return $field->isValueEmpty($element->getFieldValue($attribute), $element);
            }
        }

        return empty($element->$attribute);
    }

    /**
     * Renders the given elements using their partial templates.
     *
     * If no partial template exists for an element, its string representation will be output instead.
     *
     * @param  ElementInterface[]  $elements
     */
    public static function renderElements(array $elements, array $variables = []): Markup
    {
        $output = array_map(fn (ElementInterface $element) => (string) $element->render($variables), $elements);

        return new Markup(implode("\n", $output), Craft::$app->charset);
    }

    /**
     * Returns a generic editor URL for the given element.
     */
    public static function elementEditorUrl(ElementInterface $element, bool $withParams = true): string
    {
        $url = sprintf('edit/%s', $element->getCanonicalId());

        if ($element->slug && ! self::isTempSlug($element->slug)) {
            $url .= "-$element->slug";
        }

        if (! $withParams) {
            return Url::cpUrl($url);
        }

        return self::addElementEditorUrlParams($url, $element);
    }

    /**
     * Ensures the given element edit URL includes the necessary query string params.
     */
    public static function addElementEditorUrlParams(string $url, ElementInterface $element): string
    {
        $params = [];

        if (SitesFacade::isMultiSite()) {
            $params['site'] = $element->getSite()->handle;
        }

        if ($element->getIsDraft() && ! $element->isProvisionalDraft) {
            $params['draftId'] = $element->draftId;
        } elseif ($element->getIsRevision()) {
            $params['revisionId'] = $element->revisionId;
        }

        return Url::cpUrl($url, $params);
    }

    /**
     * Returns the URL that users should be redirected to after editing the given element.
     */
    public static function postEditUrl(ElementInterface $element): string
    {
        if ($element instanceof NestedElementInterface) {
            $ownerEditUrl = $element->getOwner()?->getCpEditUrl();

            if ($ownerEditUrl) {
                return $ownerEditUrl;
            }
        }

        return $element->getPostEditUrl() ?? Cms::config()->getPostCpLoginRedirect();
    }

    /**
     * Returns a generic URL for viewing an element's revisions.
     */
    public static function elementRevisionsUrl(ElementInterface $element): string
    {
        $url = sprintf('revisions/%s', $element->getCanonicalId());

        if ($element->slug && ! self::isTempSlug($element->slug)) {
            $url .= "-$element->slug";
        }

        return Url::cpUrl($url);
    }

    /**
     * Returns whether the given element is a multi-site element.
     */
    public static function isMultiSite(ElementInterface $element): bool
    {
        $supportedSites = self::supportedSitesForElement($element, true);

        if (count($supportedSites) <= 1) {
            return false;
        }

        $propagatedSites = array_filter($supportedSites, fn (array $site) => $site['propagate']);

        return count($propagatedSites) > 1;
    }

    private static function renderUriFormat(string $uriFormat, ElementInterface $element): string
    {
        $variables = [];

        if (! $element->id) {
            $element->tempId = 'id-'.Str::random(10);

            if (str_contains($uriFormat, '{id')) {
                $variables['id'] = $element->tempId;
            }

            if (! $element->getCanonicalId()) {
                if (str_contains($uriFormat, '{canonicalId')) {
                    $variables['canonicalId'] = $element->tempId;
                }

                if (str_contains($uriFormat, '{sourceId')) {
                    $variables['sourceId'] = $element->tempId;
                }
            }
        }

        $uri = renderObjectTemplate($uriFormat, $element, $variables);

        return preg_replace('/^\/+|(?<=\/)\/+|\/+$/', '', $uri);
    }

    private static function isUniqueUri(string $testUri, ElementInterface $element): bool
    {
        $rows = DB::table(Table::ELEMENTS_SITES, 'elements_sites')
            ->select(['elements.id', 'elements.type'])
            ->join(new Alias(Table::ELEMENTS, 'elements'), 'elements.id', '=', 'elements_sites.elementId')
            ->where('elements_sites.siteId', $element->siteId)
            ->whereNull(['elements.draftId', 'elements.revisionId', 'elements.dateDeleted'])
            ->where('elements_sites.uriLower', mb_strtolower($testUri))
            ->when(
                value: $element->getCanonicalId(),
                callback: fn (Builder $query, int $sourceId) => $query->whereNot('elements.id', $sourceId),
            )
            ->get();

        if ($rows->isEmpty()) {
            return true;
        }

        foreach ($rows as $row) {
            $conflictingElement = Craft::$app->getElements()->getElementById($row->id, $row->type, $element->siteId);

            if ($conflictingElement && ! self::isDraftOrRevision($conflictingElement)) {
                return false;
            }
        }

        return true;
    }
}
