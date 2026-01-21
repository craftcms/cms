<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\base\Element;
use craft\base\ElementActionInterface;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\NestedElementInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User as UserElement;
use craft\errors\FieldNotFoundException;
use craft\errors\OperationAbortedException;
use craft\fieldlayoutelements\CustomField;
use craft\i18n\Locale;
use craft\services\ElementSources;
use DateTime;
use Throwable;
use Twig\Markup;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;

/**
 * Class ElementHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ElementHelper
{
    private const URI_MAX_LENGTH = 255;

    private static ?UserElement $provisionalDraftUser = null;

    /**
     * Generates a new temporary slug.
     *
     * @return string
     * @since 3.2.2
     */
    public static function tempSlug(): string
    {
        return '__temp_' . StringHelper::randomString();
    }

    /**
     * Returns whether the given slug is temporary.
     *
     * @param string $slug
     * @return bool
     * @since 3.2.2
     */
    public static function isTempSlug(string $slug): bool
    {
        return str_starts_with($slug, '__temp_');
    }

    /**
     * Generates a new slug based on a given string.
     *
     * This is different from [[normalizeSlug()]] in two ways:
     *
     * - Periods and underscores will be converted to dashes, whereas [[normalizeSlug()]] will leave those in-tact.
     * - The string may be converted to ASCII.
     *
     * @param string $str The string
     * @param bool|null $ascii Whether the slug should be converted to ASCII. If null, it will depend on
     * the <config5:limitAutoSlugsToAscii> config setting value.
     * @param string|null $language The language to pull ASCII character mappings for, if needed
     * @return string
     * @since 3.5.0
     */
    public static function generateSlug(string $str, ?bool $ascii = null, ?string $language = null): string
    {
        // Replace periods, underscores, and hyphens with spaces so they get separated with the slugWordSeparator
        // to mimic the default JavaScript-based slug generation.
        $slug = str_replace(['.', '_', '-'], ' ', $str);

        if ($ascii ?? Craft::$app->getConfig()->getGeneral()->limitAutoSlugsToAscii) {
            $slug = StringHelper::toAscii($slug, $language);
        }

        return static::normalizeSlug($slug);
    }

    /**
     * Normalizes a slug.
     *
     * @param string $slug
     * @return string
     * @since 3.5.0
     */
    public static function normalizeSlug(string $slug): string
    {
        // Special case for the homepage
        if ($slug === Element::HOMEPAGE_URI) {
            return $slug;
        }

        // Remove HTML tags
        $slug = StringHelper::stripHtml($slug);

        // Remove inner-word punctuation
        $slug = preg_replace('/[\'"‘’“”ʻ\[\]\(\)\{\}:]/u', '', $slug);

        // Make it lowercase
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if (!$generalConfig->allowUppercaseInSlug) {
            $slug = mb_strtolower($slug);
        }

        // Get the "words". Split on anything that is not alphanumeric or allowed punctuation
        // Reference: http://www.regular-expressions.info/unicode.html
        $words = ArrayHelper::filterEmptyStringsFromArray(preg_split('/[^\p{L}\p{N}\p{M}\._\-]+/u', $slug));

        return implode($generalConfig->slugWordSeparator, $words);
    }

    /**
     * Sets the URI on an element using a given URL format, tweaking its slug if necessary to ensure it's unique.
     *
     * @param ElementInterface $element
     * @throws OperationAbortedException if a unique URI could not be found
     */
    public static function setUniqueUri(ElementInterface $element): void
    {
        $uriFormat = $element->getUriFormat();

        // No URL format, no URI.
        if ($uriFormat === null) {
            $element->uri = null;
            return;
        }

        // If the URL format returns an empty string, the URL format probably wrapped everything in a condition
        $testUri = self::_renderUriFormat($uriFormat, $element);
        if ($testUri === '') {
            $element->uri = null;
            return;
        }

        // Does the URL format even have a {slug} tag?
        if (!static::doesUriFormatHaveSlugTag($uriFormat)) {
            // Make sure it's unique
            if (!self::_isUniqueUri($testUri, $element)) {
                throw new OperationAbortedException('Could not find a unique URI for this element');
            }

            $element->uri = $testUri;
            return;
        }

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $maxSlugIncrement = Craft::$app->getConfig()->getGeneral()->maxSlugIncrement;
        $originalSlug = $element->slug ?? '';
        $originalSlugLen = mb_strlen($originalSlug);

        for ($i = 1; $i <= $maxSlugIncrement; $i++) {
            $suffix = ($i !== 1) ? $generalConfig->slugWordSeparator . $i : '';
            $element->slug = $originalSlug . $suffix;
            $testUri = self::_renderUriFormat($uriFormat, $element);

            // Make sure we're not over our max length.
            $testUriLen = mb_strlen($testUri);
            if ($testUriLen > self::URI_MAX_LENGTH) {
                // See how much over we are.
                $overage = $testUriLen - self::URI_MAX_LENGTH;

                // If the slug is too small to be trimmed down, we're SOL
                if ($overage >= $originalSlugLen) {
                    $element->slug = $originalSlug;
                    throw new OperationAbortedException('Could not find a unique URI for this element');
                }

                $trimmedSlug = mb_substr($originalSlug, 0, -$overage);
                if ($generalConfig->slugWordSeparator) {
                    $trimmedSlug = rtrim($trimmedSlug, $generalConfig->slugWordSeparator);
                }
                $element->slug = $trimmedSlug . $suffix;
                $testUri = self::_renderUriFormat($uriFormat, $element);
            }

            if (self::_isUniqueUri($testUri, $element)) {
                // OMG!
                $element->uri = $testUri;
                return;
            }
        }

        $element->slug = $originalSlug;
        throw new OperationAbortedException('Could not find a unique URI for this element');
    }

    /**
     * Renders and normalizes a given element URI Format.
     *
     * @param string $uriFormat
     * @param ElementInterface $element
     * @return string
     */
    private static function _renderUriFormat(string $uriFormat, ElementInterface $element): string
    {
        $variables = [];

        // If the URI format contains {id}/{canonicalId}/{sourceId} but the element doesn't have one yet, preserve the tag
        if (!$element->id) {
            $element->tempId = 'id-' . StringHelper::randomString(10);
            if (str_contains($uriFormat, '{id')) {
                $variables['id'] = $element->tempId;
            }
            if (!$element->getCanonicalId()) {
                if (str_contains($uriFormat, '{canonicalId')) {
                    $variables['canonicalId'] = $element->tempId;
                }
                if (str_contains($uriFormat, '{sourceId')) {
                    $variables['sourceId'] = $element->tempId;
                }
            }
        }

        $uri = Craft::$app->getView()->renderObjectTemplate($uriFormat, $element, $variables);

        // Remove any leading/trailing/double slashes
        return preg_replace('/^\/+|(?<=\/)\/+|\/+$/', '', $uri);
    }

    /**
     * Tests a given element URI for uniqueness.
     *
     * @param string $testUri
     * @param ElementInterface $element
     * @return bool
     */
    private static function _isUniqueUri(string $testUri, ElementInterface $element): bool
    {
        $query = (new Query())
            ->select(['elements.id', 'elements.type'])
            ->from(['elements_sites' => Table::ELEMENTS_SITES])
            ->innerJoin(['elements' => Table::ELEMENTS], '[[elements.id]] = [[elements_sites.elementId]]')
            ->where([
                'elements_sites.siteId' => $element->siteId,
                'elements.draftId' => null,
                'elements.revisionId' => null,
                'elements.dateDeleted' => null,
            ]);

        if (Craft::$app->getDb()->getIsMysql()) {
            $query->andWhere([
                'elements_sites.uri' => $testUri,
            ]);
        } else {
            // Postgres is case-sensitive
            $query->andWhere([
                'lower([[elements_sites.uri]])' => mb_strtolower($testUri),
            ]);
        }

        if (($sourceId = $element->getCanonicalId()) !== null) {
            $query->andWhere([
                'not', [
                    'elements.id' => $sourceId,
                ],
            ]);
        }

        $info = $query->all();

        if (empty($info)) {
            return true;
        }

        // Make sure the element(s) isn't owned by a draft/revision
        foreach ($info as $row) {
            $conflictingElement = Craft::$app->getElements()->getElementById($row['id'], $row['type'], $element->siteId);
            if ($conflictingElement && !static::isDraftOrRevision($conflictingElement)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns whether a given URL format has a proper {slug} tag.
     *
     * @param string $uriFormat
     * @return bool
     */
    public static function doesUriFormatHaveSlugTag(string $uriFormat): bool
    {
        return (bool)preg_match('/\bslug\b/', $uriFormat);
    }

    /**
     * Returns a list of sites that a given element supports.
     *
     * Each site is represented as an array with `siteId`, `propagate`, and `enabledByDefault` keys.
     *
     * @param ElementInterface $element The element to return supported site info for
     * @param bool $withUnpropagatedSites Whether to include sites the element is currently not being propagated to
     * @return array[]
     * @throws Exception if any of the element’s supported sites are invalid
     */
    public static function supportedSitesForElement(ElementInterface $element, bool $withUnpropagatedSites = false): array
    {
        $sites = [];
        $siteUidMap = ArrayHelper::map(Craft::$app->getSites()->getAllSites(true), 'id', 'uid');

        foreach ($element->getSupportedSites() as $site) {
            if (!is_array($site)) {
                $site = [
                    'siteId' => (int)$site,
                ];
            } else {
                if (!isset($site['siteId'])) {
                    throw new Exception('Missing "siteId" key in ' . get_class($element) . '::getSupportedSites()');
                }
                $site['siteId'] = (int)$site['siteId'];
            }

            if (!isset($siteUidMap[$site['siteId']])) {
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
     * @param ElementInterface $element The element to return site statuses for
     * @param bool $editableOnly Whether to only return statuses for sites the user has access to
     * @return array<int,bool> The site statuses, indexed by site ID
     * @since 4.4.7
     */
    public static function siteStatusesForElement(ElementInterface $element, bool $editableOnly = false): array
    {
        $supportedSites = static::supportedSitesForElement($element, true);
        $propagatedSites = array_values(array_filter($supportedSites, fn($site) => $site['propagate']));
        $propagatedSiteIds = array_map(fn($site) => $site['siteId'], $propagatedSites);

        if ($editableOnly) {
            $propagatedSiteIds = array_intersect($propagatedSiteIds, Craft::$app->getSites()->getEditableSiteIds());
        }

        if (!$element->enabled || !$element->id) {
            // If the element isn't saved yet, assume other sites will share its current status
            $defaultStatus = !$element->id && $element->enabled && $element->getEnabledForSite();
            return array_combine($propagatedSiteIds, array_map(fn() => $defaultStatus, $propagatedSiteIds));
        }

        $siteStatusesQuery = $element::find()
            ->drafts($element->getIsDraft())
            ->provisionalDrafts($element->isProvisionalDraft)
            ->revisions($element->getIsRevision())
            ->id($element->id)
            ->siteId($propagatedSiteIds)
            ->status(null)
            ->trashed(null)
            ->asArray()
            ->select(['elements_sites.siteId', 'elements_sites.enabled']);

        return array_map(fn($enabled) => (bool)$enabled, $siteStatusesQuery->pairs());
    }

    /**
     * Returns whether changes should be tracked for the given element.
     *
     * @param ElementInterface $element
     * @return bool
     * @since 3.7.4
     */
    public static function shouldTrackChanges(ElementInterface $element): bool
    {
        return (
            $element->id &&
            $element->siteSettingsId &&
            $element->duplicateOf === null &&
            $element::trackChanges() &&
            !$element->mergingCanonicalChanges &&
            !$element->resaving
        );
    }

    /**
     * Returns whether the given element is editable by the current user, taking user permissions into account.
     *
     * @param ElementInterface $element
     * @return bool
     */
    public static function isElementEditable(ElementInterface $element): bool
    {
        $user = Craft::$app->getUser()->getIdentity();

        if ($user && Craft::$app->getElements()->canView($element, $user)) {
            if (!Craft::$app->getIsMultiSite()) {
                return true;
            }

            foreach (static::supportedSitesForElement($element) as $siteInfo) {
                if ($user->can(sprintf('editSite:%s', $siteInfo['siteUid']))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns the editable site IDs for a given element, taking user permissions into account.
     *
     * @param ElementInterface $element
     * @return array
     */
    public static function editableSiteIdsForElement(ElementInterface $element): array
    {
        $siteIds = [];
        $user = Craft::$app->getUser()->getIdentity();

        if ($user && Craft::$app->getElements()->canView($element, $user)) {
            if (Craft::$app->getIsMultiSite()) {
                foreach (static::supportedSitesForElement($element) as $siteInfo) {
                    if ($user->can(sprintf('editSite:%s', $siteInfo['siteUid']))) {
                        $siteIds[] = $siteInfo['siteId'];
                    }
                }
            } else {
                $siteIds[] = Craft::$app->getSites()->getPrimarySite()->id;
            }
        }

        return $siteIds;
    }

    /**
     * Returns the root owner of a given element.
     *
     * @param ElementInterface $element
     * @return ElementInterface
     * @since 3.2.0
     * @deprecated in 5.4.0. Use [[ElementInterface::getRootOwner()]] instead.
     */
    public static function rootElement(ElementInterface $element): ElementInterface
    {
        return $element->getRootOwner();
    }

    /**
     * Returns the root element of a given element, unless the element or any of its owners are not canonical.
     *
     * @param ElementInterface $element
     * @return ElementInterface|null
     * @since 5.0.0
     */
    public static function rootElementIfCanonical(ElementInterface $element): ?ElementInterface
    {
        if (!$element->getIsCanonical()) {
            return null;
        }

        if ($element instanceof NestedElementInterface) {
            $owner = $element->getOwner();
            if ($owner) {
                return static::rootElementIfCanonical($owner);
            }
        }

        return $element;
    }

    /**
     * Returns whether the given element (or its root element if a block element) is a draft.
     *
     * @param ElementInterface $element
     * @return bool
     * @since 3.7.0
     */
    public static function isDraft(ElementInterface $element): bool
    {
        if ($element->getIsDraft()) {
            return true;
        }

        // Defer to the owner element, if there is one
        if ($element instanceof NestedElementInterface) {
            $owner = $element->getOwner();
            if ($owner) {
                return static::isDraft($owner);
            }
        }

        return false;
    }

    /**
     * Returns whether the given element (or its root element if a block element) is a revision.
     *
     * @param ElementInterface $element
     * @return bool
     * @since 3.7.0
     */
    public static function isRevision(ElementInterface $element): bool
    {
        if ($element->getIsRevision()) {
            return true;
        }

        // Defer to the owner element, if there is one
        if ($element instanceof NestedElementInterface) {
            $owner = $element->getOwner();
            if ($owner) {
                return static::isRevision($owner);
            }
        }

        return false;
    }

    /**
     * Returns whether the given element (or its root element if a block element) is a draft or revision.
     *
     * @param ElementInterface $element
     * @return bool
     * @since 3.2.0
     */
    public static function isDraftOrRevision(ElementInterface $element): bool
    {
        if ($element->getIsDraft() || $element->getIsRevision()) {
            return true;
        }

        // Defer to the owner element, if there is one
        if ($element instanceof NestedElementInterface) {
            $owner = $element->getOwner();
            if ($owner) {
                return static::isDraftOrRevision($owner);
            }
        }

        return false;
    }

    /**
     * Returns whether the given element (or its root element if a block element) is a canonical element.
     *
     * @param ElementInterface $element
     * @return bool
     * @since 3.7.17
     */
    public static function isCanonical(ElementInterface $element): bool
    {
        return $element->getRootOwner()->getIsCanonical();
    }

    /**
     * Returns whether the given element (or its root element if a block element) is a derivative of another element.
     *
     * @param ElementInterface $element
     * @return bool
     * @since 3.7.17
     */
    public static function isDerivative(ElementInterface $element): bool
    {
        return $element->getRootOwner()->getIsDerivative();
    }

    /**
     * Returns whether the given derivative element is outdated compared to its canonical element.
     *
     * @param ElementInterface $element
     * @return bool
     * @since 3.7.12
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

        if (!$element->dateLastMerged) {
            return true;
        }

        return $element->dateLastMerged < $canonical->dateUpdated;
    }

    /**
     * Returns the canonical version of an element.
     *
     * @param ElementInterface $element The source/draft/revision element
     * @param bool $anySite Whether the source element can be retrieved in any site
     * @return ElementInterface
     * @since 3.3.0
     * @deprecated in 3.7.0. Use [[ElementInterface::getCanonical()]] instead.
     */
    public static function sourceElement(ElementInterface $element, bool $anySite = false): ElementInterface
    {
        return $element->getCanonical($anySite);
    }

    /**
     * Given an array of elements, will go through and set the appropriate "next"
     * and "prev" elements on them.
     *
     * @param iterable|ElementInterface[] $elements The array of elements.
     */
    public static function setNextPrevOnElements(iterable $elements): void
    {
        /** @var ElementInterface|null $lastElement */
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
     * Returns the root level source key for a given source key/path
     *
     * @param string $sourceKey
     * @return string
     * @since 3.7.25.1
     */
    public static function rootSourceKey(string $sourceKey): string
    {
        $pos = strpos($sourceKey, '/');
        return $pos !== false ? substr($sourceKey, 0, $pos) : $sourceKey;
    }

    /**
     * Returns an element type's source definition based on a given source key/path and context.
     *
     * @param class-string<ElementInterface> $elementType The element type class
     * @param string $sourceKey The source key/path
     * @param string $context The context
     * @param bool $withDisabled Whether disabled sources should be included
     * @param string|null $page The page to fetch sources for
     * @return array|null The source definition, or null if it cannot be found
     */
    public static function findSource(
        string $elementType,
        string $sourceKey,
        string $context = ElementSources::CONTEXT_INDEX,
        bool $withDisabled = false,
        ?string $page = null,
    ): ?array {
        $path = explode('/', $sourceKey);
        $sources = Craft::$app->getElementSources()->getSources($elementType, $context, $withDisabled, $page);
        $rootSource = null;

        while ($path) {
            $key = array_shift($path);
            $source = null;

            foreach ($sources as $testSource) {
                if (isset($testSource['key']) && $testSource['key'] === $key) {
                    $source = $testSource;
                    break;
                }
            }

            if ($source === null) {
                break;
            }

            // Is that the end of the path?
            if (empty($path)) {
                // Is this a nested source?
                if (isset($rootSource)) {
                    $source['type'] = $rootSource['type'];
                    $source['keyPath'] = $sourceKey;
                }

                return $source;
            }

            // Prepare for searching nested sources
            if ($rootSource === null) {
                $rootSource = $source;
            }
            $sources = $source['nested'] ?? [];
        }

        if (!str_starts_with($sourceKey, 'custom:')) {
            // Let the element get involved
            $source = $elementType::findSource($sourceKey, $context);
            if ($source) {
                $source['type'] = ElementSources::TYPE_NATIVE;
                return $source;
            }
        }

        return null;
    }

    /**
     * Returns the description of a field’s translation support.
     *
     * @param string $translationMethod
     * @return string|null
     * @since 3.5.0
     */
    public static function translationDescription(string $translationMethod): ?string
    {
        return match ($translationMethod) {
            Field::TRANSLATION_METHOD_SITE => Craft::t('app', 'This field is translated for each site.'),
            Field::TRANSLATION_METHOD_SITE_GROUP => Craft::t('app', 'This field is translated for each site group.'),
            Field::TRANSLATION_METHOD_LANGUAGE => Craft::t('app', 'This field is translated for each language.'),
            default => null,
        };
    }

    /**
     * Returns the translation key for an element title or custom field, based on the given translation method
     * and translation key format.
     *
     * @param ElementInterface $element
     * @param string $translationMethod
     * @param string|null $translationKeyFormat
     * @return string
     * @since 3.5.0
     */
    public static function translationKey(ElementInterface $element, string $translationMethod, ?string $translationKeyFormat = null): string
    {
        switch ($translationMethod) {
            case Field::TRANSLATION_METHOD_NONE:
                return '1';
            case Field::TRANSLATION_METHOD_SITE:
                return (string)$element->siteId;
            case Field::TRANSLATION_METHOD_SITE_GROUP:
                return (string)$element->getSite()->groupId;
            case Field::TRANSLATION_METHOD_LANGUAGE:
                return $element->getSite()->language;
            default:
                // Translate for each site if a translation key format wasn’t specified
                if ($translationKeyFormat === null) {
                    return (string)$element->siteId;
                }
                return Craft::$app->getView()->renderObjectTemplate($translationKeyFormat, $element);
        }
    }

    /**
     * Returns whether the attribute on the given element is empty.
     *
     * @param ElementInterface $element
     * @param string $attribute
     * @return bool
     * @since 4.2.6
     */
    public static function isAttributeEmpty(ElementInterface $element, string $attribute): bool
    {
        // See if we're setting a custom field
        $fieldLayout = $element->getFieldLayout();
        if ($fieldLayout) {
            foreach ($fieldLayout->getTabs() as $tab) {
                foreach ($tab->getElements() as $layoutElement) {
                    if ($layoutElement instanceof CustomField && $layoutElement->attribute() === $attribute) {
                        try {
                            $field = $layoutElement->getField();
                        } catch (FieldNotFoundException) {
                            continue;
                        }
                        return $field->isValueEmpty($element->getFieldValue($attribute), $element);
                    }
                }
            }
        }

        return empty($element->$attribute);
    }

    /**
     * Returns the HTML for a given attribute value, to be shown in table and card views.
     *
     * @param mixed $value The field value
     * @return string
     * @since 5.0.0
     */
    public static function attributeHtml(mixed $value): string
    {
        if ($value instanceof DateTime) {
            $formatter = Craft::$app->getFormatter();
            return Html::tag('span', $formatter->asTimestamp($value, Locale::LENGTH_SHORT), [
                'title' => $formatter->asDatetime($value, Locale::LENGTH_SHORT),
            ]);
        }

        if (is_bool($value)) {
            if (!$value) {
                return '';
            }

            return Html::tag('span', '', [
                'class' => 'checkbox-icon',
                'role' => 'img',
                'title' => Craft::t('app', 'Enabled'),
                'aria' => [
                    'label' => Craft::t('app', 'Enabled'),
                ],
            ]);
        }

        if ($value instanceof Markup) {
            return (string)$value;
        }

        try {
            $value = (string)$value;
        } catch (Throwable) {
            return '';
        }

        return Html::encode(StringHelper::stripHtml($value));
    }

    /**
     * Returns the HTML for a link attribute based on provided URL.
     *
     * @param string|null $url
     * @return string
     * @since 5.5.0
     */
    public static function linkAttributeHtml(?string $url): string
    {
        return Html::beginTag('a',  [
            'href' => $url,
            'rel' => 'noopener',
            'target' => '_blank',
            'title' => Craft::t('app', 'Visit webpage'),
            'aria-label' => Craft::t('app', 'View'),
        ]) .
        Html::tag('span', Cp::iconSvg('world'), [
            'class' => ['cp-icon', 'small', 'inline-flex'],
        ]) .
        Html::endTag('a');
    }

    /**
     * Returns the HTML for URI attribute based on a value (text) and a URL it's supposed to link to.
     *
     * @param string|null $value
     * @param string|null $url
     * @return string
     * @since 5.5.0
     */
    public static function uriAttributeHtml(?string $value, ?string $url): string
    {
        return Html::a(Html::tag('span', $value, ['dir' => 'ltr']), $url, [
            'href' => $url,
            'rel' => 'noopener',
            'target' => '_blank',
            'class' => 'go',
            'title' => Craft::t('app', 'Visit webpage'),
        ]);
    }

    /**
     * Returns the searchable attributes for a given element, ensuring that `slug` and `title` are included.
     *
     * @param ElementInterface $element
     * @return string[]
     * @since 4.6.0
     */
    public static function searchableAttributes(ElementInterface $element): array
    {
        $searchableAttributes = array_flip($element::searchableAttributes());
        $searchableAttributes['slug'] = true;
        if ($element::hasTitles()) {
            $searchableAttributes['title'] = true;
        }
        return array_keys($searchableAttributes);
    }

    /**
     * Returns a generic editor URL for the given element.
     *
     * @param ElementInterface $element
     * @param bool $withParams Whether to include the necessary query string params
     * @return string
     * @since 5.0.0
     */
    public static function elementEditorUrl(ElementInterface $element, bool $withParams = true): string
    {
        $url = sprintf('edit/%s', $element->getCanonicalId());

        if ($element->slug && !static::isTempSlug($element->slug)) {
            $url .= "-$element->slug";
        }

        if ($withParams) {
            return static::addElementEditorUrlParams($url, $element);
        }

        return UrlHelper::cpUrl($url);
    }

    /**
     * Ensures the given element edit URL includes the necessary query string params.
     *
     * @param string $url
     * @param ElementInterface $element
     * @return string
     * @since 5.0.0
     */
    public static function addElementEditorUrlParams(string $url, ElementInterface $element): string
    {
        $params = [];

        if (Craft::$app->getIsMultiSite()) {
            $params['site'] = $element->getSite()->handle;
        }

        if ($element->getIsDraft() && !$element->isProvisionalDraft) {
            $params['draftId'] = $element->draftId;
        } elseif ($element->getIsRevision()) {
            $params['revisionId'] = $element->revisionId;
        }

        return UrlHelper::cpUrl($url, $params);
    }

    /**
     * Returns the URL that users should be redirected to after editing the given element.
     *
     * @param ElementInterface $element
     * @return string
     * @since 5.2.0
     */
    public static function postEditUrl(ElementInterface $element): string
    {
        if ($element instanceof NestedElementInterface) {
            // redirect to the owner's edit page, if possible
            $ownerEditUrl = $element->getOwner()?->getCpEditUrl();
            if ($ownerEditUrl) {
                return $ownerEditUrl;
            }
        }

        return $element->getPostEditUrl() ?? Craft::$app->getConfig()->getGeneral()->getPostCpLoginRedirect();
    }

    /**
     * Returns an element action’s JavaScript configuration.
     *
     * @param ElementActionInterface $action
     * @return array
     * @since 5.0.0
     */
    public static function actionConfig(ElementActionInterface $action): array
    {
        return [
            'type' => $action::class,
            'destructive' => $action->isDestructive(),
            'download' => $action->isDownload(),
            'name' => $action->getTriggerLabel(),
            'trigger' => $action->getTriggerHtml(),
            'confirm' => $action->getConfirmationMessage(),
            'settings' => $action->getSettings() ?: null,
        ];
    }

    /**
     * Renders the given elements using their partial templates.
     *
     * If no partial template exists for an element, its string representation will be output instead.
     *
     * @param ElementInterface[] $elements
     * @param array $variables
     * @return Markup
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @since 5.0.0
     */
    public static function renderElements(array $elements, array $variables = []): Markup
    {
        $output = array_map(fn(ElementInterface $element) => (string)$element->render($variables), $elements);
        return new Markup(implode("\n", $output), Craft::$app->charset);
    }

    /**
     * Swaps out any canonical elements with provisional drafts, when they exist.
     *
     * @template T of ElementInterface
     * @param T[] $elements
     * @since 5.2.0
     */
    public static function swapInProvisionalDrafts(array &$elements): void
    {
        /** @var T[] $drafts */
        $drafts = self::provisionalDrafts($elements);

        if (empty($drafts)) {
            return;
        }

        // array_filter() preserves keys, so it's safe to loop through it rather than $elements here
        foreach ($elements as $i => $element) {
            if (isset($drafts[$element->id])) {
                $draft = $drafts[$element->id];
                $draft->setCanonical($element);

                // retain canonical element structure data => ['root', 'lft', 'rgt', 'level']
                if ($element->structureId !== null) {
                    $draft->structureId = $element->structureId;
                    $draft->root = $element->root;
                    $draft->lft = $element->lft;
                    $draft->rgt = $element->rgt;
                    $draft->level = $element->level;
                }

                // retain the canonical element's ownerId
                if ($element instanceof NestedElementInterface && $draft instanceof NestedElementInterface) {
                    $draft->setOwnerId($element->getOwnerId());
                }

                $elements[$i] = $draft;
            }
        }
    }

    /**
     * Swaps out any canonical elements with provisional drafts, when they exist.
     *
     * @template T of ElementInterface
     * @param T[] $elements
     * @since 5.9.0
     */
    public static function loadProvisionalChanges(array $elements): void
    {
        $drafts = self::provisionalDrafts($elements);

        if (empty($drafts)) {
            return;
        }

        // array_filter() preserves keys, so it's safe to loop through it rather than $elements here
        foreach ($elements as $element) {
            if (isset($drafts[$element->id])) {
                $draft = $drafts[$element->id];
                $element->hasProvisionalChanges = true;

                foreach ($draft->getModifiedAttributes() as $name) {
                    if ($element->canSetProperty($name)) {
                        $element->$name = $draft->$name;
                    }
                }

                foreach ($draft->getModifiedFields() as $handle) {
                    $element->setFieldValue($handle, $draft->getFieldValue($handle));
                }
            }
        }
    }

    /**
     * @param ElementInterface[] $elements
     * @return ElementInterface[]
     */
    private static function provisionalDrafts(array $elements): array
    {
        $user = self::$provisionalDraftUser ?? Craft::$app->getUser()->getIdentity();
        if (!$user) {
            return [];
        }

        // filter out drafts and revisions
        // (don't just exclude derivative elements though! see https://github.com/craftcms/cms/issues/16626)
        $canonicalElements = array_filter(
            $elements,
            fn(ElementInterface $element) => !$element->getIsDraft() && !$element->getIsRevision(),
        );

        if (empty($canonicalElements)) {
            return [];
        }

        $first = reset($canonicalElements);

        return $first::find()
            ->draftOf($canonicalElements)
            ->draftCreator($user)
            ->provisionalDrafts()
            ->siteId($first->siteId)
            ->status(null)
            ->indexBy('canonicalId')
            ->all();
    }

    /**
     * Returns whether the given element is a multi-site element.
     *
     * @param ElementInterface $element
     * @return bool
     * @throws Exception
     * @since 5.8.0
     */
    public static function isMultiSite(ElementInterface $element): bool
    {
        // Site info
        $supportedSites = self::supportedSitesForElement($element, true);
        if (count($supportedSites) <= 1) {
            return false;
        }

        $propSites = array_filter($supportedSites, fn($site) => $site['propagate']);
        return count($propSites) > 1;
    }

    /**
     * Sets user to be used for swapping in provisional drafts.
     *
     * @param UserElement|null $user
     * @since 5.8.0
     */
    public static function setProvisionalDraftUser(?UserElement $user): void
    {
        self::$provisionalDraftUser = $user;
    }
}
