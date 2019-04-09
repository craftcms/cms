<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\errors\OperationAbortedException;
use yii\base\Exception;

/**
 * Class ElementHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ElementHelper
{
    // Public Methods
    // =========================================================================

    /**
     * Creates a slug based on a given string.
     *
     * @param string $str
     * @return string
     */
    public static function createSlug(string $str): string
    {
        // Remove HTML tags
        $str = StringHelper::stripHtml($str);

        // Convert to kebab case
        $glue = Craft::$app->getConfig()->getGeneral()->slugWordSeparator;
        $lower = !Craft::$app->getConfig()->getGeneral()->allowUppercaseInSlug;
        $str = StringHelper::toKebabCase($str, $glue, $lower, false);

        return $str;
    }

    /**
     * Sets the URI on an element using a given URL format, tweaking its slug if necessary to ensure it's unique.
     *
     * @param ElementInterface $element
     * @throws OperationAbortedException if a unique URI could not be found
     */
    public static function setUniqueUri(ElementInterface $element)
    {
        /** @var Element $element */
        $uriFormat = $element->getUriFormat();

        // No URL format, no URI.
        if ($uriFormat === null) {
            $element->uri = null;

            return;
        }

        // Does the URL format even have a {slug} tag?
        if (!static::doesUriFormatHaveSlugTag($uriFormat)) {
            $testUri = self::_renderUriFormat($uriFormat, $element);

            // Make sure it's unique
            if (!self::_isUniqueUri($testUri, $element)) {
                throw new OperationAbortedException('Could not find a unique URI for this element');
            }

            $element->uri = $testUri;

            return;
        }

        $slugWordSeparator = Craft::$app->getConfig()->getGeneral()->slugWordSeparator;
        $maxSlugIncrement = Craft::$app->getConfig()->getGeneral()->maxSlugIncrement;

        for ($i = 0; $i < $maxSlugIncrement; $i++) {
            $testSlug = $element->slug;

            if ($i > 0) {
                $testSlug .= $slugWordSeparator . $i;
            }

            $originalSlug = $element->slug;
            $element->slug = $testSlug;

            $testUri = self::_renderUriFormat($uriFormat, $element);

            // Make sure we're not over our max length.
            if (mb_strlen($testUri) > 255) {
                // See how much over we are.
                $overage = mb_strlen($testUri) - 255;

                // Do we have anything left to chop off?
                if ($overage < mb_strlen($element->slug)) {
                    // Chop off the overage amount from the slug
                    $element->slug = mb_substr($element->slug, 0, -$overage);

                    // Let's try this again.
                    $i--;
                    continue;
                }

                // We're screwed, blow things up.
                throw new OperationAbortedException('Could not find a unique URI for this element');
            }

            if (self::_isUniqueUri($testUri, $element)) {
                // OMG!
                $element->slug = $testSlug;
                $element->uri = $testUri;

                return;
            }

            // Try again...
            $element->slug = $originalSlug;
        }

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
        /** @var Element $element */
        $variables = [];

        // If the URI format contains {id} but the element doesn't have one yet, preserve the {id} tag
        if (!$element->id && strpos($uriFormat, '{id') !== false) {
            $variables['id'] = $element->tempId = 'id-' . StringHelper::randomString(10);
        }

        $uri = Craft::$app->getView()->renderObjectTemplate($uriFormat, $element, $variables);

        // Remove any leading/trailing/double slashes
        $uri = preg_replace('/^\/+|(?<=\/)\/+|\/+$/', '', $uri);

        return $uri;
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
        /** @var Element $element */
        $query = (new Query())
            ->from(['{{%elements_sites}} elements_sites'])
            ->innerJoin('{{%elements}} elements', '[[elements.id]] = [[elements_sites.elementId]]')
            ->where([
                'elements_sites.siteId' => $element->siteId,
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

        if ($element->id) {
            $query->andWhere(['not', ['elements.id' => $element->id]]);
        }

        return (int)$query->count() === 0;
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
     * Each site is represented as an array with 'siteId' and 'enabledByDefault' keys.
     *
     * @param ElementInterface $element
     * @return array
     * @throws Exception if any of the element's supported sites are invalid
     */
    public static function supportedSitesForElement(ElementInterface $element): array
    {
        $sites = [];
        $siteUidMap = ArrayHelper::map(Craft::$app->getSites()->getAllSites(), 'id', 'uid');

        foreach ($element->getSupportedSites() as $site) {
            if (!is_array($site)) {
                $site = [
                    'siteId' => $site,
                ];
            } else if (!isset($site['siteId'])) {
                throw new Exception('Missing "siteId" key in ' . get_class($element) . '::getSupportedSites()');
            }

            $site['siteUid'] = $siteUidMap[$site['siteId']];

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
     * @return bool
     */
    public static function isElementEditable(ElementInterface $element): bool
    {
        if ($element->getIsEditable()) {
            if (Craft::$app->getIsMultiSite()) {
                foreach (static::supportedSitesForElement($element) as $siteInfo) {
                    if (Craft::$app->getUser()->checkPermission('editSite:' . $siteInfo['siteUid'])) {
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
     * @return array
     */
    public static function editableSiteIdsForElement(ElementInterface $element): array
    {
        $siteIds = [];

        if ($element->getIsEditable()) {
            if (Craft::$app->getIsMultiSite()) {
                foreach (static::supportedSitesForElement($element) as $siteInfo) {
                    if (Craft::$app->getUser()->checkPermission('editSite:' . $siteInfo['siteUid'])) {
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
     * Given an array of elements, will go through and set the appropriate "next"
     * and "prev" elements on them.
     *
     * @param ElementInterface[] $elements The array of elements.
     */
    public static function setNextPrevOnElements(array $elements)
    {
        /** @var ElementInterface $lastElement */
        $lastElement = null;

        foreach ($elements as $i => $element) {
            if ($lastElement) {
                $lastElement->setNext($element);
                $element->setPrev($lastElement);
            } else {
                $element->setPrev(false);
            }

            $lastElement = $element;
        }

        if ($lastElement) {
            $lastElement->setNext(false);
        }
    }

    /**
     * Returns an element type's source definition based on a given source key/path and context.
     *
     * @param string $elementType The element type class
     * @param string $sourceKey The source key/path
     * @param string|null $context The context
     * @return array|null The source definition, or null if it cannot be found
     */
    public static function findSource(string $elementType, string $sourceKey, string $context = null)
    {
        /** @var string|ElementInterface $elementType */
        $path = explode('/', $sourceKey);
        $sources = $elementType::sources($context);

        while (!empty($path)) {
            $key = array_shift($path);
            $source = null;

            foreach ($sources as $testSource) {
                if (isset($testSource['key']) && $testSource['key'] === $key) {
                    $source = $testSource;
                    break;
                }
            }

            if ($source === null) {
                return null;
            }

            // Is that the end of the path?
            if (empty($path)) {
                // If this is a nested source, set the full path on it so we don't forget it
                if ($source['key'] !== $sourceKey) {
                    $source['keyPath'] = $sourceKey;
                }

                return $source;
            }

            // Prepare for searching nested sources
            $sources = $source['nested'] ?? [];
        }

        return null;
    }
}
