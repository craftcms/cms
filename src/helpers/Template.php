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
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\i18n\Locale;
use craft\web\twig\variables\Paginate;
use yii\base\BaseObject;
use yii\base\UnknownMethodException;

/**
 * Class Template
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Template
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the attribute value for a given array/object.
     *
     * @param \Twig_Environment $env
     * @param \Twig_Source $source
     * @param mixed $object The object or array from where to get the item
     * @param mixed $item The item to get from the array or object
     * @param array $arguments An array of arguments to pass if the item is an object method
     * @param string $type The type of attribute (@see Twig_Template constants)
     * @param bool $isDefinedTest Whether this is only a defined check
     * @param bool $ignoreStrictCheck Whether to ignore the strict attribute check or not
     * @return mixed The attribute value, or a Boolean when $isDefinedTest is true, or null when the attribute is not set and $ignoreStrictCheck is true
     * @throws \Twig_Error_Runtime if the attribute does not exist and Twig is running in strict mode and $isDefinedTest is false
     * @internal
     */
    public static function attribute(\Twig_Environment $env, \Twig_Source $source, $object, $item, array $arguments = [], string $type = \Twig_Template::ANY_CALL, bool $isDefinedTest = false, bool $ignoreStrictCheck = false)
    {
        if ($object instanceof ElementInterface) {
            self::_includeElementInTemplateCaches($object);
        }

        if (
            $type !== \Twig_Template::METHOD_CALL &&
            $object instanceof BaseObject &&
            $object->canGetProperty($item)
        ) {
            return $isDefinedTest ? true : $object->$item;
        }

        // Convert any Twig_Markup arguments back to strings (unless the class *extends* Twig_Markup)
        foreach ($arguments as $key => $value) {
            if (is_object($value) && get_class($value) === \Twig_Markup::class) {
                $arguments[$key] = (string)$value;
            }
        }

        // Add deprecated support for the old DateTime methods
        if ($object instanceof \DateTime && ($value = self::_dateTimeAttribute($object, $item, $type)) !== false) {
            return $value;
        }

        try {
            return \twig_get_attribute($env, $source, $object, $item, $arguments, $type, $isDefinedTest, $ignoreStrictCheck);
        } catch (UnknownMethodException $e) {
            // Copy twig_get_attribute()'s BadMethodCallException handling
            if ($ignoreStrictCheck || !$env->isStrictVariables()) {
                return null;
            }
            throw new \Twig_Error_Runtime($e->getMessage(), -1, $source);
        }
    }

    /**
     * Paginates an element query's results
     *
     * @param ElementQueryInterface $query
     * @return array
     */
    public static function paginateCriteria(ElementQueryInterface $query): array
    {
        /** @var ElementQuery $query */
        $currentPage = Craft::$app->getRequest()->getPageNum();
        $limit = $query->limit;

        // Get the total result count, without applying the limit
        $query->limit = null;
        $total = (int)$query->count();
        $query->limit = $limit;

        // Bail out early if there are no results. Also avoids a divide by zero bug in the calculation of $totalPages
        if ($total === 0) {
            return [new Paginate(), $query->all()];
        }

        // If they specified limit as null or 0 (for whatever reason), just assume it's all going to be on one page.
        if (!$limit) {
            $limit = $total;
        }

        $totalPages = (int)ceil($total / $limit);

        $paginateVariable = new Paginate();

        if ($totalPages === 0) {
            return [$paginateVariable, []];
        }

        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }

        $offset = $limit * ($currentPage - 1);

        // Is there already an offset set?
        if ($query->offset) {
            $offset += $query->offset;
        }

        $last = $offset + $limit;

        if ($last > $total) {
            $last = $total;
        }

        $paginateVariable->first = $offset + 1;
        $paginateVariable->last = $last;
        $paginateVariable->total = $total;
        $paginateVariable->currentPage = $currentPage;
        $paginateVariable->totalPages = $totalPages;

        // Fetch the elements
        $originalOffset = $query->offset;
        $query->offset = (int)$offset;
        $elements = $query->all();
        $query->offset = $originalOffset;

        return [$paginateVariable, $elements];
    }

    /**
     * Returns a string wrapped in a \Twig_Markup object
     *
     * @param string $value
     * @return \Twig_Markup
     */
    public static function raw(string $value): \Twig_Markup
    {
        return new \Twig_Markup($value, Craft::$app->charset);
    }

    // Private Methods
    // =========================================================================

    /**
     * Includes an element in any active template caches.
     *
     * @param ElementInterface $element
     */
    private static function _includeElementInTemplateCaches(ElementInterface $element)
    {
        /** @var Element $element */
        $elementId = $element->id;

        // Don't initialize the TemplateCaches service if we don't have to
        if ($elementId && Craft::$app->has('templateCaches', true)) {
            Craft::$app->getTemplateCaches()->includeElementInTemplateCaches($elementId);
        }
    }

    /**
     * Adds (deprecated) support for the old Craft\DateTime methods.
     *
     * @param \DateTime $object
     * @param string $item
     * @param string $type
     * @return string|false
     */
    private static function _dateTimeAttribute(\DateTime $object, string $item, string $type)
    {
        switch ($item) {
            case 'atom':
                $format = \DateTime::ATOM;
                $filter = 'atom';
                break;
            case 'cookie':
                $format = \DateTime::COOKIE;
                break;
            case 'iso8601':
                $format = \DateTime::ISO8601;
                break;
            case 'rfc822':
                $format = \DateTime::RFC822;
                break;
            case 'rfc850':
                $format = \DateTime::RFC850;
                break;
            case 'rfc1036':
                $format = \DateTime::RFC1036;
                break;
            case 'rfc1123':
                $format = \DateTime::RFC1123;
                break;
            case 'rfc2822':
                $format = \DateTime::RFC2822;
                break;
            case 'rfc3339':
                $format = \DateTime::RFC3339;
                break;
            case 'rss':
                $format = \DateTime::RSS;
                $filter = 'rss';
                break;
            case 'w3c':
                $format = \DateTime::W3C;
                break;
            case 'w3cDate':
                $format = 'Y-m-d';
                break;
            case 'mySqlDateTime':
                $format = 'Y-m-d H:i:s';
                break;
            case 'localeDate':
                $value = Craft::$app->getFormatter()->asDate($object, Locale::LENGTH_SHORT);
                $filter = 'date(\'short\')';
                break;
            case 'localeTime':
                $value = Craft::$app->getFormatter()->asTime($object, Locale::LENGTH_SHORT);
                $filter = 'time(\'short\')';
                break;
            case 'year':
                $format = 'Y';
                break;
            case 'month':
                $format = 'n';
                break;
            case 'day':
                $format = 'j';
                break;
            case 'nice':
                $value = Craft::$app->getFormatter()->asDatetime($object, Locale::LENGTH_SHORT);
                $filter = 'datetime(\'short\')';
                break;
            case 'uiTimestamp':
                $value = Craft::$app->getFormatter()->asTimestamp($object, Locale::LENGTH_SHORT);
                $filter = 'timestamp(\'short\')';
                break;
            default:
                return false;
        }

        if (isset($format)) {
            if (!isset($value)) {
                $value = $object->format($format);
            }
            if (!isset($filter)) {
                $filter = 'date(\'' . addslashes($format) . '\')';
            }
        }

        $key = "DateTime::{$item}()";
        /** @noinspection PhpUndefinedVariableInspection */
        $message = "DateTime::{$item}" . ($type === \Twig_Template::METHOD_CALL ? '()' : '') . " is deprecated. Use the |{$filter} filter instead.";

        if ($item === 'iso8601') {
            $message = rtrim($message, '.') . ', or consider using the |atom filter, which will give you an actual ISO-8601 string (unlike the old .iso8601() method).';
        }

        Craft::$app->getDeprecator()->log($key, $message);
        /** @noinspection PhpUndefinedVariableInspection */
        return $value;
    }
}
