<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\helpers;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\web\twig\variables\Paginate;
use yii\base\Object;

/**
 * Class Template
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Template
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the attribute value for a given array/object.
     *
     * @param \Twig_Environment $env
     * @param \Twig_Source      $source
     * @param mixed             $object            The object or array from where to get the item
     * @param mixed             $item              The item to get from the array or object
     * @param array             $arguments         An array of arguments to pass if the item is an object method
     * @param string            $type              The type of attribute (@see Twig_Template constants)
     * @param bool              $isDefinedTest     Whether this is only a defined check
     * @param bool              $ignoreStrictCheck Whether to ignore the strict attribute check or not
     *
     * @return mixed The attribute value, or a Boolean when $isDefinedTest is true, or null when the attribute is not set and $ignoreStrictCheck is true
     *
     * @throws \Twig_Error_Runtime if the attribute does not exist and Twig is running in strict mode and $isDefinedTest is false
     *
     * @internal
     */
    public static function attribute(\Twig_Environment $env, \Twig_Source $source, $object, $item, array $arguments = [], string $type = \Twig_Template::ANY_CALL, bool $isDefinedTest = false, bool $ignoreStrictCheck = false)
    {
        if ($object instanceof ElementInterface) {
            self::_includeElementInTemplateCaches($object);
        }

        if (
            $type !== \Twig_Template::METHOD_CALL &&
            $object instanceof Object &&
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

        return \twig_get_attribute($env, $source, $object, $item, $arguments, $type, $isDefinedTest, $ignoreStrictCheck);
    }

    /**
     * Paginates an element query's results
     *
     * @param ElementQueryInterface $query
     *
     * @return array
     */
    public static function paginateCriteria(ElementQueryInterface $query): array
    {
        /** @var ElementQuery $query */
        $currentPage = Craft::$app->getRequest()->getPageNum();
        $limit = $query->limit;

        // Get the total result count, without applying the limit
        $query->limit = null;
        $total = $query->count();
        $query->limit = $limit;

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
        $paginateVariable->total = (int)$total;
        $paginateVariable->currentPage = $currentPage;
        $paginateVariable->totalPages = $totalPages;

        // Copy the criteria, set the offset, and get the elements
        $query = clone $query;
        $query->offset = (int)$offset;
        $elements = $query->all();

        return [$paginateVariable, $elements];
    }

    /**
     * Returns a string wrapped in a \Twig_Markup object
     *
     * @param string $value
     *
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
     *
     * @return void
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
}
