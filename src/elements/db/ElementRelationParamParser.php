<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\db\Query;
use craft\db\Table;
use craft\fields\BaseRelationField;
use craft\fields\Matrix;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use craft\models\Site;
use Illuminate\Support\Collection;
use yii\base\BaseObject;
use yii\base\InvalidArgumentException;

/**
 * Parses a relatedTo param on an ElementQuery.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ElementRelationParamParser extends BaseObject
{
    public const DIR_FORWARD = 0;
    public const DIR_REVERSE = 1;

    /**
     * @var int
     */
    private static int $_relateSourceMatrixBlocksCount = 0;

    /**
     * @var int
     */
    private static int $_relateTargetMatrixBlocksCount = 0;

    /**
     * @var int
     */
    private static int $_relateSourcesCount = 0;

    /**
     * @var int
     */
    private static int $_relateTargetsCount = 0;

    /**
     * @var FieldInterface[]|null The custom fields that are game for the query.
     */
    public ?array $fields = null;

    /**
     * Normalizes a `relatedTo` param for [[parse()]].
     *
     * @param array|string|int|ElementInterface $relatedToParam
     * @param int|string|int[]|null $siteId
     * @return array
     * @throws InvalidArgumentException if any of the relation criteria contain an invalid site handle
     * @since 3.6.11
     */
    public static function normalizeRelatedToParam(mixed $relatedToParam, array|int|string|null $siteId = null): array
    {
        // Ensure it's an array
        if (!is_array($relatedToParam)) {
            if (is_string($relatedToParam)) {
                $relatedToParam = StringHelper::split($relatedToParam);
            } elseif ($relatedToParam instanceof Collection) {
                $relatedToParam = $relatedToParam->all();
            } else {
                $relatedToParam = [$relatedToParam];
            }
        }

        if (
            isset($relatedToParam[0]) &&
            is_string($relatedToParam[0]) &&
            in_array($relatedToParam[0], ['and', 'or'])
        ) {
            $glue = array_shift($relatedToParam);
            if ($glue === 'and' && count($relatedToParam) < 2) {
                $glue = 'or';
            }
        } else {
            $glue = 'or';
        }

        if (isset($relatedToParam['element']) || isset($relatedToParam['sourceElement']) || isset($relatedToParam['targetElement'])) {
            $relatedToParam = [$relatedToParam];
        }

        $relatedToParam = Collection::make($relatedToParam)->map(
            fn($relatedToParam) => static::normalizeRelatedToCriteria($relatedToParam, $siteId)
        )->all();

        if ($glue === 'or') {
            // Group all of the OR elements, so we avoid adding massive JOINs to the query
            $orElements = [];

            foreach ($relatedToParam as $i => $relCriteria) {
                if (
                    isset($relCriteria['element']) &&
                    $relCriteria['element'][0] === 'or'
                    && $relCriteria['field'] === null &&
                    $relCriteria['sourceSite'] === null
                ) {
                    array_push($orElements, ...array_slice($relCriteria['element'], 1));
                    unset($relatedToParam[$i]);
                }
            }

            if (!empty($orElements)) {
                $relatedToParam[] = static::normalizeRelatedToCriteria($orElements, $siteId);
            }
        }

        array_unshift($relatedToParam, $glue);
        return $relatedToParam;
    }

    /**
     * Normalizes an individual `relatedTo` criteria.
     *
     * @param mixed $relCriteria
     * @param int|string|int[]|null $siteId
     * @return array
     * @throws InvalidArgumentException if the criteria contains an invalid site handle
     * @since 3.6.11
     */
    public static function normalizeRelatedToCriteria(mixed $relCriteria, array|int|string|null $siteId = null): array
    {
        if (
            !is_array($relCriteria) ||
            (!isset($relCriteria['element']) && !isset($relCriteria['sourceElement']) && !isset($relCriteria['targetElement']))
        ) {
            $relCriteria = ['element' => $relCriteria];
        }

        // Merge in default criteria params
        $relCriteria += [
            'field' => null,
            'sourceSite' => $siteId,
        ];

        // Normalize the sourceSite param (should be an ID)
        if ($relCriteria['sourceSite']) {
            if (
                !is_numeric($relCriteria['sourceSite']) &&
                (!is_array($relCriteria['sourceSite']) || !ArrayHelper::isNumeric($relCriteria['sourceSite']))
            ) {
                if ($relCriteria['sourceSite'] instanceof Site) {
                    $relCriteria['sourceSite'] = $relCriteria['sourceSite']->id;
                } else {
                    $site = Craft::$app->getSites()->getSiteByHandle($relCriteria['sourceSite']);
                    if (!$site) {
                        // Invalid handle
                        throw new InvalidArgumentException("Invalid site: {$relCriteria['sourceSite']}");
                    }
                    $relCriteria['sourceSite'] = $site->id;
                }
            }
        }

        // Normalize the elements
        foreach (['element', 'sourceElement', 'targetElement'] as $elementParam) {
            if (isset($relCriteria[$elementParam])) {
                $elements = &$relCriteria[$elementParam];

                if (!is_array($elements)) {
                    if (is_string($elements)) {
                        $elements = StringHelper::split($elements);
                    } elseif ($elements instanceof Collection) {
                        $elements = $elements->all();
                    } else {
                        $elements = [$elements];
                    }
                }

                if (
                    isset($elements[0]) &&
                    is_string($elements[0]) &&
                    in_array($elements[0], ['and', 'or'])
                ) {
                    $glue = array_shift($elements);
                    if ($glue === 'and' && count($elements) < 2) {
                        $glue = 'or';
                    }
                } else {
                    $glue = 'or';
                }

                array_unshift($elements, $glue);
                break;
            }
        }

        return $relCriteria;
    }

    /**
     * Parses a `relatedTo` element query param and returns the condition that should
     * be applied back on the element query, or `false` if there's an issue.
     *
     * @param mixed $relatedToParam
     * @param int|string|int[]|null $siteId
     * @return array|false
     */
    public function parse(mixed $relatedToParam, array|int|string|null $siteId = null): array|false
    {
        $relatedToParam = static::normalizeRelatedToParam($relatedToParam, $siteId);
        $glue = array_shift($relatedToParam);

        if (empty($relatedToParam)) {
            return false;
        }

        $conditions = [];

        foreach ($relatedToParam as $relCriteria) {
            $condition = $this->_subparse($relCriteria);

            if ($condition) {
                $conditions[] = $condition;
            } elseif ($glue === 'or') {
                continue;
            } else {
                return false;
            }
        }

        if (empty($conditions)) {
            return false;
        }

        if (count($conditions) === 1) {
            return $conditions[0];
        }

        array_unshift($conditions, $glue);

        return $conditions;
    }

    /**
     * Parses a part of a relatedTo element query param and returns the condition or `false` if there's an issue.
     *
     * @param mixed $relCriteria
     * @return mixed
     */
    private function _subparse(mixed $relCriteria): mixed
    {
        // Get the element IDs, wherever they are
        $relElementIds = [];
        $relSourceElementIds = [];
        $glue = 'or';

        $elementParams = ['element', 'sourceElement', 'targetElement'];

        foreach ($elementParams as $elementParam) {
            if (isset($relCriteria[$elementParam])) {
                $elements = $relCriteria[$elementParam];
                $glue = array_shift($elements);

                foreach ($elements as $element) {
                    if (is_numeric($element)) {
                        $relElementIds[] = $element;
                        if ($elementParam === 'element') {
                            $relSourceElementIds[] = $element;
                        }
                    } elseif ($element instanceof ElementInterface) {
                        if ($elementParam === 'targetElement') {
                            $relElementIds[] = $element->getCanonicalId();
                        } else {
                            $relElementIds[] = $element->id;
                            if ($elementParam === 'element') {
                                $relSourceElementIds[] = $element->getCanonicalId();
                            }
                        }
                    } elseif ($element instanceof ElementQueryInterface) {
                        $ids = $element->ids();
                        array_push($relElementIds, ...$ids);
                        if ($elementParam === 'element') {
                            array_push($relSourceElementIds, ...$ids);
                        }
                    }
                }

                break;
            }
        }

        if (empty($relElementIds)) {
            return false;
        }

        // Going both ways?
        if ($elementParam === 'element') {
            array_unshift($relElementIds, $glue);

            return $this->parse([
                'or',
                [
                    'sourceElement' => $relElementIds,
                    'field' => $relCriteria['field'],
                    'sourceSite' => $relCriteria['sourceSite'],
                ],
                [
                    'targetElement' => $relSourceElementIds,
                    'field' => $relCriteria['field'],
                    'sourceSite' => $relCriteria['sourceSite'],
                ],
            ]);
        }

        // Figure out which direction we’re going
        if ($elementParam === 'sourceElement') {
            $dir = self::DIR_FORWARD;
        } else {
            $dir = self::DIR_REVERSE;
        }

        // Do we need to check for *all* of the element IDs?
        if ($glue === 'and') {
            // Spread it across multiple relation sub-params
            $newRelatedToParam = ['and'];

            foreach ($relElementIds as $elementId) {
                $newRelatedToParam[] = [$elementParam => [$elementId]];
            }

            return $this->parse($newRelatedToParam);
        }

        $conditions = [];
        $relationFieldIds = [];

        if ($relCriteria['field']) {
            // Loop through all of the fields in this rel criteria, create the Matrix-specific conditions right away
            // and save the normal field IDs for later
            $fields = $relCriteria['field'];
            if (!is_array($fields)) {
                $fields = is_string($fields) ? StringHelper::split($fields) : [$fields];
            }

            foreach ($fields as $field) {
                if (($fieldModel = $this->_getField($field, $fieldHandleParts)) === null) {
                    Craft::warning('Attempting to load relations for an invalid field: ' . $field);

                    return false;
                }

                if ($fieldModel instanceof BaseRelationField) {
                    // We'll deal with normal relation fields all together
                    $relationFieldIds[] = $fieldModel->id;
                } elseif ($fieldModel instanceof Matrix) {
                    $blockTypeFieldIds = [];

                    // Searching by a specific block type field?
                    if (isset($fieldHandleParts[1])) {
                        // There could be more than one block type field with this handle, so we must loop through all
                        // of the block types on this Matrix field
                        $blockTypes = Craft::$app->getMatrix()->getBlockTypesByFieldId($fieldModel->id);

                        foreach ($blockTypes as $blockType) {
                            foreach ($blockType->getCustomFields() as $blockTypeField) {
                                if ($blockTypeField->handle == $fieldHandleParts[1]) {
                                    $blockTypeFieldIds[] = $blockTypeField->id;
                                    break;
                                }
                            }
                        }

                        if (empty($blockTypeFieldIds)) {
                            continue;
                        }
                    }

                    if ($dir === self::DIR_FORWARD) {
                        self::$_relateSourcesCount++;
                        self::$_relateTargetMatrixBlocksCount++;

                        $sourcesAlias = 'sources' . self::$_relateSourcesCount;
                        $targetMatrixBlocksAlias = 'target_matrixblocks' . self::$_relateTargetMatrixBlocksCount;
                        $targetMatrixElementsAlias = 'target_matrixelements' . self::$_relateTargetMatrixBlocksCount;

                        $subQuery = (new Query())
                            ->select(["$sourcesAlias.targetId"])
                            ->from([$sourcesAlias => Table::RELATIONS])
                            ->innerJoin([$targetMatrixBlocksAlias => Table::MATRIXBLOCKS], "[[$targetMatrixBlocksAlias.id]] = [[$sourcesAlias.sourceId]]")
                            ->innerJoin([$targetMatrixElementsAlias => Table::ELEMENTS], "[[$targetMatrixElementsAlias.id]] = [[$targetMatrixBlocksAlias.id]]")
                            ->where([
                                "$targetMatrixBlocksAlias.primaryOwnerId" => $relElementIds,
                                "$targetMatrixBlocksAlias.fieldId" => $fieldModel->id,
                                "$targetMatrixElementsAlias.enabled" => true,
                                "$targetMatrixElementsAlias.dateDeleted" => null,
                            ]);

                        if ($relCriteria['sourceSite']) {
                            $subQuery->andWhere([
                                'or',
                                ["$sourcesAlias.sourceSiteId" => null],
                                ["$sourcesAlias.sourceSiteId" => $relCriteria['sourceSite']],
                            ]);
                        }

                        if (!empty($blockTypeFieldIds)) {
                            $subQuery->andWhere(["$sourcesAlias.fieldId" => $blockTypeFieldIds]);
                        }
                    } else {
                        self::$_relateSourceMatrixBlocksCount++;
                        $sourceMatrixBlocksAlias = 'source_matrixblocks' . self::$_relateSourceMatrixBlocksCount;
                        $sourceMatrixElementsAlias = 'source_matrixelements' . self::$_relateSourceMatrixBlocksCount;
                        $matrixBlockTargetsAlias = 'matrixblock_targets' . self::$_relateSourceMatrixBlocksCount;

                        $subQuery = (new Query())
                            ->select(["$sourceMatrixBlocksAlias.primaryOwnerId"])
                            ->from([$sourceMatrixBlocksAlias => Table::MATRIXBLOCKS])
                            ->innerJoin([$sourceMatrixElementsAlias => Table::ELEMENTS], "[[$sourceMatrixElementsAlias.id]] = [[$sourceMatrixBlocksAlias.id]]")
                            ->innerJoin([$matrixBlockTargetsAlias => Table::RELATIONS], "[[$matrixBlockTargetsAlias.sourceId]] = [[$sourceMatrixBlocksAlias.id]]")
                            ->where([
                                "$sourceMatrixElementsAlias.enabled" => true,
                                "$sourceMatrixElementsAlias.dateDeleted" => null,
                                "$matrixBlockTargetsAlias.targetId" => $relElementIds,
                                "$sourceMatrixBlocksAlias.fieldId" => $fieldModel->id,
                            ]);

                        if ($relCriteria['sourceSite']) {
                            $subQuery->andWhere([
                                'or',
                                ["$matrixBlockTargetsAlias.sourceSiteId" => null],
                                ["$matrixBlockTargetsAlias.sourceSiteId" => $relCriteria['sourceSite']],
                            ]);
                        }

                        if (!empty($blockTypeFieldIds)) {
                            $subQuery->andWhere(["$matrixBlockTargetsAlias.fieldId" => $blockTypeFieldIds]);
                        }
                    }

                    $conditions[] = ['elements.id' => $subQuery];
                    unset($subQuery);
                } else {
                    Craft::warning('Attempting to load relations for a non-relational field: ' . $fieldModel->handle);

                    return false;
                }
            }
        }

        // If there were no fields, or there are some non-Matrix fields, add the normal relation condition. (Basically,
        // run this code if the rel criteria wasn't exclusively for Matrix.)
        if (empty($relCriteria['field']) || !empty($relationFieldIds)) {
            if ($dir === self::DIR_FORWARD) {
                self::$_relateSourcesCount++;
                $relTableAlias = 'sources' . self::$_relateSourcesCount;
                $relConditionColumn = 'sourceId';
                $relElementColumn = 'targetId';
            } else {
                self::$_relateTargetsCount++;
                $relTableAlias = 'targets' . self::$_relateTargetsCount;
                $relConditionColumn = 'targetId';
                $relElementColumn = 'sourceId';
            }

            $subQuery = (new Query())
                ->select(["$relTableAlias.$relElementColumn"])
                ->from([$relTableAlias => Table::RELATIONS])
                ->where(["$relTableAlias.$relConditionColumn" => $relElementIds]);

            if ($relCriteria['sourceSite']) {
                $subQuery->andWhere([
                    'or',
                    ["$relTableAlias.sourceSiteId" => null],
                    ["$relTableAlias.sourceSiteId" => $relCriteria['sourceSite']],
                ]);
            }

            if (!empty($relationFieldIds)) {
                $subQuery->andWhere(["$relTableAlias.fieldId" => $relationFieldIds]);
            }

            $conditions[] = ['elements.id' => $subQuery];
        }

        if (empty($conditions)) {
            return false;
        }

        if (count($conditions) == 1) {
            return $conditions[0];
        }

        array_unshift($conditions, 'or');

        return $conditions;
    }

    /**
     * Returns a field model based on its handle or ID.
     *
     * @param mixed $field
     * @param array|null $fieldHandleParts
     * @return FieldInterface|null
     */
    private function _getField(mixed $field, ?array &$fieldHandleParts = null): ?FieldInterface
    {
        if (is_numeric($field)) {
            $fieldHandleParts = null;
            return Craft::$app->getFields()->getFieldById($field);
        }

        $fieldHandleParts = explode('.', $field);
        return $this->fields[$fieldHandleParts[0]] ?? null;
    }
}
