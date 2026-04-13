<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use CraftCms\Cms\Element\Contracts\ElementActionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementAttributeRenderer;
use CraftCms\Cms\Element\ElementHelper as LaravelElementHelper;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Users;
use Illuminate\Support\Facades\Context;

use Twig\Markup;
use function CraftCms\Cms\renderObjectTemplate;

/**
 * Class ElementHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 {@see \CraftCms\Cms\Element\ElementHelper} should be used for core element helper APIs, {@see ElementSources} for source lookup, {@see ElementAttributeRenderer} for attribute rendering, {@see Drafts} for provisional draft helpers, {@see TranslationMethod} for translation helpers, and {@see Context} with {@see Drafts::CONTEXT_PREVIEW_USER_ID} for preview-user context.
 */
class ElementHelper
{
    /**
     * Generates a new temporary slug.
     *
     * @return string
     * @since 3.2.2
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::tempSlug()} instead.
     */
    public static function tempSlug(): string
    {
        return LaravelElementHelper::tempSlug();
    }

    /**
     * Returns whether the given slug is temporary.
     *
     * @param string $slug
     *
     * @return bool
     * @since 3.2.2
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::isTempSlug()} instead.
     */
    public static function isTempSlug(string $slug): bool
    {
        return LaravelElementHelper::isTempSlug($slug);
    }

    /**
     * Generates a new slug based on a given string.
     *
     * This is different from [[normalizeSlug()]] in two ways:
     *
     * - Periods and underscores will be converted to dashes, whereas [[normalizeSlug()]] will leave those in-tact.
     * - The string may be converted to ASCII.
     *
     * @param string $value The string
     * @param bool|null $ascii Whether the slug should be converted to ASCII. If null, it will depend on
     * the <config5:limitAutoSlugsToAscii> config setting value.
     * @param string|null $language The language to pull ASCII character mappings for, if needed
     *
     * @return string
     * @since 3.5.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::generateSlug()} instead.
     */
    public static function generateSlug(string $value, ?bool $ascii = null, ?string $language = null): string
    {
        return LaravelElementHelper::generateSlug($value, $ascii, $language);
    }

    /**
     * Normalizes a slug.
     *
     * @param string $slug
     *
     * @return string
     * @since 3.5.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::normalizeSlug()} instead.
     */
    public static function normalizeSlug(string $slug): string
    {
        return LaravelElementHelper::normalizeSlug($slug);
    }

    /**
     * Sets the URI on an element using a given URL format, tweaking its slug if necessary to ensure it's unique.
     *
     * @param ElementInterface $element
     *
     * @see \CraftCms\Cms\Element\ElementHelper::setUniqueUri()
     * @throws \CraftCms\Cms\Shared\Exceptions\OperationAbortedException if a unique URI could not be found
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::setUniqueUri()} instead.
     */
    public static function setUniqueUri(ElementInterface $element): void
    {
        LaravelElementHelper::setUniqueUri($element);
    }

    /**
     * Returns whether a given URL format has a proper {slug} tag.
     *
     * @param string $uriFormat
     *
     * @return bool
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::doesUriFormatHaveSlugTag()} instead.
     */
    public static function doesUriFormatHaveSlugTag(string $uriFormat): bool
    {
        return LaravelElementHelper::doesUriFormatHaveSlugTag($uriFormat);
    }

    /**
     * Returns a list of sites that a given element supports.
     *
     * Each site is represented as an array with `siteId`, `propagate`, and `enabledByDefault` keys.
     *
     * @param ElementInterface $element The element to return supported site info for
     * @param bool $withUnpropagatedSites Whether to include sites the element is currently not being propagated to
     *
     * @return array[]
     * @throws \RuntimeException if any of the element’s supported sites are invalid
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::supportedSitesForElement()} instead.
     */
    public static function supportedSitesForElement(ElementInterface $element, bool $withUnpropagatedSites = false): array
    {
        return LaravelElementHelper::supportedSitesForElement($element, $withUnpropagatedSites);
    }

    /**
     * Returns the site statuses for a given element.
     *
     * @param ElementInterface $element The element to return site statuses for
     * @param bool $editableOnly Whether to only return statuses for sites the user has access to
     *
     * @return array<int,bool> The site statuses, indexed by site ID
     * @since 4.4.7
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::siteStatusesForElement()} instead.
     */
    public static function siteStatusesForElement(ElementInterface $element, bool $editableOnly = false): array
    {
        return LaravelElementHelper::siteStatusesForElement($element, $editableOnly);
    }

    /**
     * Returns whether changes should be tracked for the given element.
     *
     * @param ElementInterface $element
     *
     * @return bool
     * @since 3.7.4
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::shouldTrackChanges()} instead.
     */
    public static function shouldTrackChanges(ElementInterface $element): bool
    {
        return LaravelElementHelper::shouldTrackChanges($element);
    }

    /**
     * Returns whether the given element is editable by the current user, taking user permissions into account.
     *
     * @param ElementInterface $element
     *
     * @return bool
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::isElementEditable()} instead.
     */
    public static function isElementEditable(ElementInterface $element): bool
    {
        return LaravelElementHelper::isElementEditable($element);
    }

    /**
     * Returns the editable site IDs for a given element, taking user permissions into account.
     *
     * @param ElementInterface $element
     *
     * @return array
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::editableSiteIdsForElement()} instead.
     */
    public static function editableSiteIdsForElement(ElementInterface $element): array
    {
        return LaravelElementHelper::editableSiteIdsForElement($element);
    }

    /**
     * Returns the root owner of a given element.
     *
     * @param ElementInterface $element
     *
     * @return ElementInterface
     * @since 3.2.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::rootElement()} instead.
     */
    public static function rootElement(ElementInterface $element): ElementInterface
    {
        return LaravelElementHelper::rootElement($element);
    }

    /**
     * Returns the root element of a given element, unless the element or any of its owners are not canonical.
     *
     * @param ElementInterface $element
     *
     * @return ElementInterface|null
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::rootElementIfCanonical()} instead.
     */
    public static function rootElementIfCanonical(ElementInterface $element): ?ElementInterface
    {
        return LaravelElementHelper::rootElementIfCanonical($element);
    }

    /**
     * Returns whether the given element (or its root element if a block element) is a draft.
     *
     * @param ElementInterface $element
     *
     * @return bool
     * @since 3.7.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::isDraft()} instead.
     */
    public static function isDraft(ElementInterface $element): bool
    {
        return LaravelElementHelper::isDraft($element);
    }

    /**
     * Returns whether the given element (or its root element if a block element) is a revision.
     *
     * @param ElementInterface $element
     *
     * @return bool
     * @since 3.7.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::isRevision()} instead.
     */
    public static function isRevision(ElementInterface $element): bool
    {
        return LaravelElementHelper::isRevision($element);
    }

    /**
     * Returns whether the given element (or its root element if a block element) is a draft or revision.
     *
     * @param ElementInterface $element
     *
     * @return bool
     * @since 3.2.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::isDraftOrRevision()} instead.
     */
    public static function isDraftOrRevision(ElementInterface $element): bool
    {
        return LaravelElementHelper::isDraftOrRevision($element);
    }

    /**
     * Returns whether the given element (or its root element if a block element) is a canonical element.
     *
     * @param ElementInterface $element
     *
     * @return bool
     * @since 3.7.17
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::isCanonical()} instead.
     */
    public static function isCanonical(ElementInterface $element): bool
    {
        return LaravelElementHelper::isCanonical($element);
    }

    /**
     * Returns whether the given element (or its root element if a block element) is a derivative of another element.
     *
     * @param ElementInterface $element
     *
     * @return bool
     * @since 3.7.17
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::isDerivative()} instead.
     */
    public static function isDerivative(ElementInterface $element): bool
    {
        return LaravelElementHelper::isDerivative($element);
    }

    /**
     * Returns whether the given derivative element is outdated compared to its canonical element.
     *
     * @param ElementInterface $element
     *
     * @return bool
     * @since 3.7.12
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::isOutdated()} instead.
     */
    public static function isOutdated(ElementInterface $element): bool
    {
        return LaravelElementHelper::isOutdated($element);
    }

    /**
     * Returns the canonical version of an element.
     *
     * @param ElementInterface $element The source/draft/revision element
     * @param bool $anySite Whether the source element can be retrieved in any site
     *
     * @return ElementInterface
     * @since 3.3.0
     * @deprecated 6.0.0 use {@see ElementInterface::getCanonical()} instead.
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
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::setNextPrevOnElements()} instead.
     */
    public static function setNextPrevOnElements(iterable $elements): void
    {
        LaravelElementHelper::setNextPrevOnElements($elements);
    }

    /**
     * Returns the root level source key for a given source key/path
     *
     * @param string $sourceKey
     *
     * @return string
     * @since 3.7.25.1
     * @deprecated 6.0.0 This method remains on the legacy helper only.
     */
    public static function rootSourceKey(string $sourceKey): string
    {
        $position = strpos($sourceKey, '/');

        return $position !== false ? substr($sourceKey, 0, $position) : $sourceKey;
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
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementSources::findSource()} instead.
     */
    public static function findSource(
        string $elementType,
        string $sourceKey,
        string $context = ElementSources::CONTEXT_INDEX,
        bool $withDisabled = false,
        ?string $page = null,
    ): ?array {
        return app(ElementSources::class)->findSource($elementType, $sourceKey, $context, $withDisabled, $page);
    }

    /**
     * Returns the description of a field’s translation support.
     *
     * @param string|TranslationMethod $translationMethod
     *
     * @return string|null
     * @since 3.5.0
     * @deprecated 6.0.0 use {@see TranslationMethod::description()} instead.
     */
    public static function translationDescription(string|TranslationMethod $translationMethod): ?string
    {
        if (!$translationMethod instanceof TranslationMethod) {
            $translationMethod = TranslationMethod::tryFrom($translationMethod);
        }

        return $translationMethod?->description();
    }

    /**
     * Returns the translation key for an element title or custom field, based on the given translation method
     * and translation key format.
     *
     * @param ElementInterface $element
     * @param string|TranslationMethod $translationMethod
     * @param string|null $translationKeyFormat
     *
     * @return string
     * @since 3.5.0
     * @deprecated 6.0.0 use {@see TranslationMethod::elementKey()} instead.
     */
    public static function translationKey(
        ElementInterface $element,
        string|TranslationMethod $translationMethod,
        ?string $translationKeyFormat = null,
    ): string {
        if (!$translationMethod instanceof TranslationMethod) {
            $translationMethod = TranslationMethod::tryFrom($translationMethod);
        }

        return $translationMethod?->elementKey($element, $translationKeyFormat)
            ?? ($translationKeyFormat === null
                ? (string) $element->siteId
                : renderObjectTemplate($translationKeyFormat, $element));
    }

    /**
     * Returns whether the attribute on the given element is empty.
     *
     * @param ElementInterface $element
     * @param string $attribute
     *
     * @return bool
     * @since 4.2.6
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::isAttributeEmpty()} instead.
     */
    public static function isAttributeEmpty(ElementInterface $element, string $attribute): bool
    {
        return LaravelElementHelper::isAttributeEmpty($element, $attribute);
    }

    /**
     * Returns the HTML for a given attribute value, to be shown in table and card views.
     *
     * @param mixed $value The field value
     *
     * @return string
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementAttributeRenderer::attributeHtml()} instead.
     */
    public static function attributeHtml(mixed $value): string
    {
        return app(ElementAttributeRenderer::class)->attributeHtml($value);
    }

    /**
     * Returns the HTML for a link attribute based on provided URL.
     *
     * @param string|null $url
     *
     * @return string
     * @since 5.5.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementAttributeRenderer::linkAttributeHtml()} instead.
     */
    public static function linkAttributeHtml(?string $url): string
    {
        return app(ElementAttributeRenderer::class)->linkAttributeHtml($url);
    }

    /**
     * Returns the HTML for URI attribute based on a value (text) and a URL it's supposed to link to.
     *
     * @param string|null $value
     * @param string|null $url
     *
     * @return string
     * @since 5.5.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementAttributeRenderer::uriAttributeHtml()} instead.
     */
    public static function uriAttributeHtml(?string $value, ?string $url): string
    {
        return app(ElementAttributeRenderer::class)->uriAttributeHtml($value, $url);
    }

    /**
     * Returns the searchable attributes for a given element, ensuring that `slug` and `title` are included.
     *
     * @param ElementInterface $element
     *
     * @return string[]
     * @since 4.6.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::searchableAttributes()} instead.
     */
    public static function searchableAttributes(ElementInterface $element): array
    {
        return LaravelElementHelper::searchableAttributes($element);
    }

    /**
     * Returns a generic editor URL for the given element.
     *
     * @param ElementInterface $element
     * @param bool $withParams Whether to include the necessary query string params
     *
     * @return string
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::elementEditorUrl()} instead.
     */
    public static function elementEditorUrl(ElementInterface $element, bool $withParams = true): string
    {
        return LaravelElementHelper::elementEditorUrl($element, $withParams);
    }

    /**
     * Ensures the given element edit URL includes the necessary query string params.
     *
     * @param string $url
     * @param ElementInterface $element
     *
     * @return string
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::addElementEditorUrlParams()} instead.
     */
    public static function addElementEditorUrlParams(string $url, ElementInterface $element): string
    {
        return LaravelElementHelper::addElementEditorUrlParams($url, $element);
    }

    /**
     * Returns the URL that users should be redirected to after editing the given element.
     *
     * @param ElementInterface $element
     *
     * @return string
     * @since 5.2.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::postEditUrl()} instead.
     */
    public static function postEditUrl(ElementInterface $element): string
    {
        return LaravelElementHelper::postEditUrl($element);
    }

    /**
     * Returns a generic URL for viewing an element’s revisions.
     *
     * @param ElementInterface $element
     * @return string
     * @since 5.9.7
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::elementRevisionsUrl()} instead.
     */
    public static function elementRevisionsUrl(ElementInterface $element): string
    {
        return LaravelElementHelper::elementRevisionsUrl($element);
    }

    /**
     * Returns an element action’s JavaScript configuration.
     *
     * @param ElementActionInterface $action
     *
     * @return array
     * @since 5.0.0
     */
    public static function actionConfig(ElementActionInterface $action): array
    {
        return LaravelElementHelper::actionConfig($action);
    }

    /**
     * Renders the given elements using their partial templates.
     *
     * If no partial template exists for an element, its string representation will be output instead.
     *
     * @param ElementInterface[] $elements
     * @param array $variables
     *
     * @return \Twig\Markup
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\NotSupportedException
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::renderElements()} instead.
     */
    public static function renderElements(array $elements, array $variables = []): Markup
    {
        return LaravelElementHelper::renderElements($elements, $variables);
    }

    /**
     * Swaps out any canonical elements with provisional drafts, when they exist.
     *
     * @param ElementInterface[] $elements
     *
     * @since 5.2.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Drafts::withProvisionalDrafts()} instead.
     */
    public static function swapInProvisionalDrafts(array &$elements): void
    {
        $elements = app(Drafts::class)->withProvisionalDrafts($elements);
    }

    /**
     * Swaps out any canonical elements with provisional drafts, when they exist.
     *
     * @template T of ElementInterface
     * @param T[] $elements
     * @since 5.9.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Drafts::loadProvisionalChanges()} instead.
     */
    public static function loadProvisionalChanges(array $elements): void
    {
        app(Drafts::class)->loadProvisionalChanges($elements);
    }

    /**
     * Returns whether the given element is a multi-site element.
     *
     * @param ElementInterface $element
     *
     * @return bool
     * @since 5.8.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementHelper::isMultiSite()} instead.
     */
    public static function isMultiSite(ElementInterface $element): bool
    {
        return LaravelElementHelper::isMultiSite($element);
    }

    /**
     * Sets user to be used for swapping in provisional drafts.
     *
     * @param UserElement|int|null $user
     *
     * @since 5.8.0
     * @deprecated 6.0.0 use {@see \Illuminate\Support\Facades\Context::addHidden()} with {@see \CraftCms\Cms\Element\Drafts::CONTEXT_PREVIEW_USER_ID} instead.
     */
    public static function setProvisionalDraftUser(UserElement|int|null $user): void
    {
        if (is_int($user)) {
            $user = app(Users::class)->getUserById($user);
        }

        Context::addHidden(Drafts::CONTEXT_PREVIEW_USER_ID, $user?->id);
    }

    /**
     * Removes values from a posted element query criteria, which would typically not be user-editable.
     *
     * @param array $criteria
     * @return array
     * @since 5.9.9
     */
    public static function cleanseQueryCriteria(array $criteria): array
    {
        return LaravelElementHelper::cleanseQueryCriteria($criteria);
    }
}
