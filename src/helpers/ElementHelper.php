<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\helpers;

use Craft;
use craft\app\base\Element;
use craft\app\base\ElementInterface;
use craft\app\db\Query;
use craft\app\elements\Asset;
use craft\app\errors\OperationAbortedException;

/**
 * Class ElementHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ElementHelper
{
    // Public Methods
    // =========================================================================

    /**
     * Sets a valid slug on a given element.
     *
     * @param ElementInterface $element
     *
     * @return void
     */
    public static function setValidSlug(ElementInterface $element)
    {
        /** @var Element $element */
        $slug = $element->slug;

        if (!$slug) {
            // Create a slug for them, based on the element's title.
            // Replace periods, underscores, and hyphens with spaces so they get separated with the slugWordSeparator
            // to mimic the default JavaScript-based slug generation.
            $slug = str_replace(['.', '_', '-'], ' ', $element->title);

            // Enforce the limitAutoSlugsToAscii config setting
            if (Craft::$app->getConfig()->get('limitAutoSlugsToAscii')) {
                $slug = StringHelper::toAscii($slug);
            }
        }

        $element->slug = static::createSlug($slug);
    }

    /**
     * Creates a slug based on a given string.
     *
     * @param string $str
     *
     * @return string
     */
    public static function createSlug($str)
    {
        // Remove HTML tags
        $str = StringHelper::stripHtml($str);

        // Convert to kebab case
        $glue = Craft::$app->getConfig()->get('slugWordSeparator');
        $lower = !Craft::$app->getConfig()->get('allowUppercaseInSlug');
        $str = StringHelper::toKebabCase($str, $glue, $lower, false);

        return $str;
    }

    /**
     * Sets the URI on an element using a given URL format, tweaking its slug if necessary to ensure it's unique.
     *
     * @param ElementInterface $element
     *
     * @throws OperationAbortedException
     */
    public static function setUniqueUri(ElementInterface $element)
    {
        /** @var Element $element */
        $urlFormat = $element->getUrlFormat();

        // No URL format, no URI.
        if (!$urlFormat) {
            $element->uri = null;

            return;
        }

        // No slug, or a URL format with no {slug}, just parse the URL format and get on with our lives
        if (!$element->slug || !static::doesUrlFormatHaveSlugTag($urlFormat)) {
            $element->uri = Craft::$app->getView()->renderObjectTemplate($urlFormat, $element);

            return;
        }

        $uniqueUriConditions = [
            'and',
            'locale = :locale',
            'uri = :uri'
        ];

        $uniqueUriParams = [
            ':locale' => $element->locale
        ];

        if ($element->id) {
            $uniqueUriConditions[] = 'elementId != :elementId';
            $uniqueUriParams[':elementId'] = $element->id;
        }

        $slugWordSeparator = Craft::$app->getConfig()->get('slugWordSeparator');
        $maxSlugIncrement = Craft::$app->getConfig()->get('maxSlugIncrement');

        for ($i = 0; $i < $maxSlugIncrement; $i++) {
            $testSlug = $element->slug;

            if ($i > 0) {
                $testSlug .= $slugWordSeparator.$i;
            }

            $originalSlug = $element->slug;
            $element->slug = $testSlug;

            $testUri = Craft::$app->getView()->renderObjectTemplate($urlFormat, $element);

            // Make sure we're not over our max length.
            if (strlen($testUri) > 255) {
                // See how much over we are.
                $overage = strlen($testUri) - 255;

                // Do we have anything left to chop off?
                if (strlen($overage) > strlen($element->slug) - strlen($slugWordSeparator.$i)) {
                    // Chop off the overage amount from the slug
                    $testSlug = $element->slug;
                    $testSlug = substr($testSlug, 0, strlen($testSlug) - $overage);

                    // Update the slug
                    $element->slug = $testSlug;

                    // Let's try this again.
                    $i -= 1;
                    continue;
                } else {
                    // We're screwed, blow things up.
                    throw new OperationAbortedException('Could not find a unique URI for this element');
                }
            }

            $uniqueUriParams[':uri'] = $testUri;

            $totalElements = (new Query())
                ->from('{{%elements_i18n}}')
                ->where($uniqueUriConditions, $uniqueUriParams)
                ->count('id');

            if ($totalElements == 0) {
                // OMG!
                $element->slug = $testSlug;
                $element->uri = $testUri;

                return;
            } else {
                $element->slug = $originalSlug;
            }
        }

        throw new OperationAbortedException('Could not find a unique URI for this element');
    }

    /**
     * Returns whether a given URL format has a proper {slug} tag.
     *
     * @param string $urlFormat
     *
     * @return boolean
     */
    public static function doesUrlFormatHaveSlugTag($urlFormat)
    {
        $element = (object)['slug' => StringHelper::randomString()];
        $uri = Craft::$app->getView()->renderObjectTemplate($urlFormat, $element);

        return StringHelper::contains($uri, $element->slug);
    }

    /**
     * Returns whether the given element is editable by the current user, taking user locale permissions into account.
     *
     * @param ElementInterface $element
     *
     * @return boolean
     */
    public static function isElementEditable(ElementInterface $element)
    {
        if ($element->getIsEditable()) {
            if (Craft::$app->getIsLocalized()) {
                foreach ($element->getLocales() as $localeId => $localeInfo) {
                    if (is_numeric($localeId) && is_string($localeInfo)) {
                        $localeId = $localeInfo;
                    }

                    if (Craft::$app->getUser()->checkPermission('editLocale:'.$localeId)) {
                        return true;
                    }
                }
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether the given element is an Asset that is a manipulatable image.
     *
     * @param Element $element
     *
     * @return boolean
     */
    public static function isAssetWithThumb(Element $element)
    {
        if ($element->getType() == 'craft\app\elements\Asset') {
            /**
             * @var $element Asset
             */
            return $element->getHasThumb();
        }
        return false;
    }
    /**
     * Returns the editable locale IDs for a given element, taking user locale permissions into account.
     *
     * @param ElementInterface $element
     *
     * @return array
     */
    public static function getEditableLocaleIdsForElement(ElementInterface $element)
    {
        $localeIds = [];

        if ($element->getIsEditable()) {
            if (Craft::$app->getIsLocalized()) {
                foreach ($element->getLocales() as $localeId => $localeInfo) {
                    if (is_numeric($localeId) && is_string($localeInfo)) {
                        $localeId = $localeInfo;
                    }

                    if (Craft::$app->getUser()->checkPermission('editLocale:'.$localeId)) {
                        $localeIds[] = $localeId;
                    }
                }
            } else {
                $localeIds[] = Craft::$app->getI18n()->getPrimarySiteLocaleId();
            }
        }

        return $localeIds;
    }
}
