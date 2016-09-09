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
use craft\app\errors\OperationAbortedException;
use yii\base\Exception;

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
        $uriFormat = $element->getUriFormat();

        // No URL format, no URI.
        if (!$uriFormat) {
            $element->uri = null;

            return;
        }

        // No slug, or a URL format with no {slug}, just parse the URL format and get on with our lives
        if (!$element->slug || !static::doesUriFormatHaveSlugTag($uriFormat)) {
            $element->uri = Craft::$app->getView()->renderObjectTemplate($uriFormat, $element);

            return;
        }

        $uniqueUriConditions = [
            'and',
            'siteId = :siteId',
            'uri = :uri'
        ];

        $uniqueUriParams = [
            ':siteId' => $element->siteId
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

            $testUri = Craft::$app->getView()->renderObjectTemplate($uriFormat, $element);

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
     * @param string $uriFormat
     *
     * @return boolean
     */
    public static function doesUriFormatHaveSlugTag($uriFormat)
    {
        $element = (object)['slug' => StringHelper::randomString()];
        $uri = Craft::$app->getView()->renderObjectTemplate($uriFormat, $element);

        return StringHelper::contains($uri, $element->slug);
    }

    /**
     * Returns a list of sites that a given element supports.
     *
     * Each site is represented as an array with 'siteId' and 'enabledByDefault' keys.
     *
     * @param ElementInterface $element
     *
     * @return array
     * @throws Exception if any of the element's supported sites are invalid
     */
    public static function getSupportedSitesForElement(ElementInterface $element)
    {
        $sites = [];

        foreach ($element->getSupportedSites() as $site) {
            if (!is_array($site)) {
                $site = [
                    'siteId' => $site,
                ];
            } else if (!isset($site['siteId'])) {
                throw new Exception('Missing "siteId" key in '.$element::className().'::getSupportedSites()');
            }
            $sites[] = array_merge([
                'enabledByDefault' => true,
            ], $site);
        }

        return $sites;
    }

    /**
     * Returns whether the given element is editable by the current user, taking user permissions into account.
     *
     * @param ElementInterface $element
     *
     * @return boolean
     */
    public static function isElementEditable(ElementInterface $element)
    {
        if ($element->getIsEditable()) {
            if (Craft::$app->getIsMultiSite()) {
                foreach (static::getSupportedSitesForElement($element) as $siteInfo) {
                    if (Craft::$app->getUser()->checkPermission('editSite:'.$siteInfo['siteId'])) {
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
     * Returns the editable site IDs for a given element, taking user permissions into account.
     *
     * @param ElementInterface $element
     *
     * @return array
     */
    public static function getEditableSiteIdsForElement(ElementInterface $element)
    {
        $siteIds = [];

        if ($element->getIsEditable()) {
            if (Craft::$app->getIsMultiSite()) {
                foreach (static::getSupportedSitesForElement($element) as $siteInfo) {
                    if (Craft::$app->getUser()->checkPermission('editSite:'.$siteInfo['siteId'])) {
                        $siteIds[] = $siteInfo['siteId'];
                    }
                }
            } else {
                $siteIds[] = Craft::$app->getSites()->getPrimarySite()->id;
            }
        }

        return $siteIds;
    }
}
