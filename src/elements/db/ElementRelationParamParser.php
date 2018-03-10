<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\db\Query;
use craft\fields\BaseRelationField;
use craft\fields\Matrix;
use craft\helpers\StringHelper;
use craft\models\Site;

/**
 * Parses a relatedTo param on an ElementQuery.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ElementRelationParamParser
{
    // Constants
    // =========================================================================

    const DIR_FORWARD = 0;
    const DIR_REVERSE = 1;

    // Properties
    // =========================================================================

    /**
     * @var int
     */
    private $_relateSourceMatrixBlocksCount = 0;

    /**
     * @var int
     */
    private $_relateTargetMatrixBlocksCount = 0;

    /**
     * @var int
     */
    private $_relateSourcesCount = 0;

    /**
     * @var int
     */
    private $_relateTargetsCount = 0;

    // Public Methods
    // =========================================================================

    /**
     * Parses a `relatedTo` element query param and returns the condition that should
     * be applied back on the element query, or `false` if there's an issue.
     *
     * @param mixed $relatedToParam
     * @return array|false
     */
    public function parse($relatedToParam)
    {
        // Ensure the criteria is an array
        if (!is_array($relatedToParam)) {
            $relatedToParam = is_string($relatedToParam) ? StringHelper::split($relatedToParam) : [$relatedToParam];
        }

        if (isset($relatedToParam['element']) || isset($relatedToParam['sourceElement']) || isset($relatedToParam['targetElement'])) {
            $relatedToParam = [$relatedToParam];
        }

        if (!isset($relatedToParam[0])) {
            return false;
        }

        $conditions = [];

        if ($relatedToParam[0] === 'and' || $relatedToParam[0] === 'or') {
            $glue = array_shift($relatedToParam);
        } else {
            $glue = 'or';
        }

        if ($glue === 'or') {
            // Group all of the unspecified elements, so we avoid adding massive JOINs to the query
            $unspecifiedElements = [];

            foreach ($relatedToParam as $i => $relCriteria) {
                if (!is_array($relCriteria)) {
                    $unspecifiedElements[] = $relCriteria;
                    unset($relatedToParam[$i]);
                }
            }

            if (!empty($unspecifiedElements)) {
                $relatedToParam[] = ['element' => $unspecifiedElements];
            }
        }

        foreach ($relatedToParam as $relCriteria) {
            $condition = $this->_subparse($relCriteria);

            if ($condition) {
                $conditions[] = $condition;
            } else if ($glue === 'or') {
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

    // Private Methods
    // =========================================================================

    /**
     * Parses a part of a relatedTo element query param and returns the condition or `false` if there's an issue.
     *
     * @param mixed $relCriteria
     * @return mixed
     */
    private function _subparse($relCriteria)
    {
        // Merge in default criteria params
        $relCriteria = array_merge([
            'field' => null,
            'sourceSite' => null,
        ], $relCriteria);

        // Check for now-deprecated sourceLocale param
        if (isset($relCriteria['sourceLocale'])) {
            Craft::$app->getDeprecator()->log('relatedTo:sourceLocale', 'The sourceLocale criteria in relatedTo element query params has been deprecated. Use sourceSite instead.');
            $relCriteria['sourceSite'] = $relCriteria['sourceLocale'];
            unset($relCriteria['sourceLocale']);
        }

        // Normalize the sourceSite param (should be an ID)
        if ($relCriteria['sourceSite'] && !is_numeric($relCriteria['sourceSite'])) {
            if ($relCriteria['sourceSite'] instanceof Site) {
                $relCriteria['sourceSite'] = $relCriteria['sourceSite']->id;
            } else {
                $site = Craft::$app->getSites()->getSiteByHandle($relCriteria['sourceSite']);
                if (!$site) {
                    // Invalid handle
                    return false;
                }
                $relCriteria['sourceSite'] = $site->id;
            }
        }

        if (!is_array($relCriteria)) {
            $relCriteria = ['element' => $relCriteria];
        }

        // Get the element IDs, wherever they are
        $relElementIds = [];
        $glue = 'or';

        $elementParams = ['element', 'sourceElement', 'targetElement'];
        $elementParam = null;

        foreach ($elementParams as $elementParam) {
            if (isset($relCriteria[$elementParam])) {
                $elements = $relCriteria[$elementParam];
                if (!is_array($elements)) {
                    $elements = is_string($elements) ? StringHelper::split($elements) : [$elements];
                }

                if (isset($elements[0]) && ($elements[0] === 'and' || $elements[0] === 'or')) {
                    $glue = array_shift($elements);
                }

                foreach ($elements as $element) {
                    if (is_numeric($element)) {
                        $relElementIds[] = $element;
                    } else if ($element instanceof ElementInterface) {
                        $relElementIds[] = $element->id;
                    } else if ($element instanceof ElementQueryInterface) {
                        foreach ($element->ids() as $id) {
                            $relElementIds[] = $id;
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
        if (isset($relCriteria['element'])) {
            array_unshift($relElementIds, $glue);

            return $this->parse([
                'or',
                [
                    'sourceElement' => $relElementIds,
                    'field' => $relCriteria['field']
                ],
                [
                    'targetElement' => $relElementIds,
                    'field' => $relCriteria['field']
                ]
            ]);
        }

        // Figure out which direction we’re going
        if (isset($relCriteria['sourceElement'])) {
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
                    Craft::warning('Attempting to load relations for an invalid field: '.$field);

                    return false;
                }

                /** @var Field $fieldModel */
                if ($fieldModel instanceof BaseRelationField) {
                    // We'll deal with normal relation fields all together
                    $relationFieldIds[] = $fieldModel->id;
                } else if ($fieldModel instanceof Matrix) {
                    $blockTypeFieldIds = [];

                    // Searching by a specific block type field?
                    if (isset($fieldHandleParts[1])) {
                        // There could be more than one block type field with this handle, so we must loop through all
                        // of the block types on this Matrix field
                        $blockTypes = Craft::$app->getMatrix()->getBlockTypesByFieldId($fieldModel->id);

                        foreach ($blockTypes as $blockType) {
                            foreach ($blockType->getFields() as $blockTypeField) {
                                /** @var Field $blockTypeField */
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
                        $this->_relateSourcesCount++;
                        $this->_relateTargetMatrixBlocksCount++;

                        $sourcesAlias = 'sources'.$this->_relateSourcesCount;
                        $targetMatrixBlocksAlias = 'target_matrixblocks'.$this->_relateTargetMatrixBlocksCount;

                        $subQuery = (new Query())
                            ->select([$sourcesAlias.'.targetId'])
                            ->from([$sourcesAlias => '{{%relations}}'])
                            ->innerJoin('{{%matrixblocks}} '.$targetMatrixBlocksAlias, "[[{$targetMatrixBlocksAlias}.id]] = [[{$sourcesAlias}.sourceId]]")
                            ->where([
                                'and',
                                ['in', $targetMatrixBlocksAlias.'.ownerId', $relElementIds],
                                [$targetMatrixBlocksAlias.'.fieldId' => $fieldModel->id]
                            ]);

                        if ($relCriteria['sourceSite']) {
                            $subQuery->andWhere([
                                'or',
                                [$sourcesAlias.'.sourceSiteId' => null],
                                [$sourcesAlias.'.sourceSiteId' => $relCriteria['sourceSite']]
                            ]);
                        }

                        if (!empty($blockTypeFieldIds)) {
                            $subQuery->andWhere(['in', $sourcesAlias.'.fieldId', $blockTypeFieldIds]);
                        }
                    } else {
                        $this->_relateSourceMatrixBlocksCount++;
                        $sourceMatrixBlocksAlias = 'source_matrixblocks'.$this->_relateSourceMatrixBlocksCount;
                        $matrixBlockTargetsAlias = 'matrixblock_targets'.$this->_relateSourceMatrixBlocksCount;

                        $subQuery = (new Query())
                            ->select([$sourceMatrixBlocksAlias.'.ownerId'])
                            ->from([$sourceMatrixBlocksAlias => '{{%matrixblocks}}'])
                            ->innerJoin('{{%relations}} '.$matrixBlockTargetsAlias, "[[{$matrixBlockTargetsAlias}.sourceId]] = [[{$sourceMatrixBlocksAlias}.id]]")
                            ->where([
                                'and',
                                ['in', $matrixBlockTargetsAlias.'.targetId', $relElementIds],
                                [$sourceMatrixBlocksAlias.'.fieldId' => $fieldModel->id]
                            ]);

                        if ($relCriteria['sourceSite']) {
                            $subQuery->andWhere([
                                'or',
                                [$matrixBlockTargetsAlias.'.sourceSiteId' => null],
                                [$matrixBlockTargetsAlias.'.sourceSiteId' => $relCriteria['sourceSite']]
                            ]);
                        }

                        if (!empty($blockTypeFieldIds)) {
                            $subQuery->andWhere(['in', $matrixBlockTargetsAlias.'.fieldId', $blockTypeFieldIds]);
                        }
                    }

                    $conditions[] = ['in', 'elements.id', $subQuery];
                    unset($subQuery);
                } else {
                    Craft::warning('Attempting to load relations for a non-relational field: '.$fieldModel->handle);

                    return false;
                }
            }
        }

        // If there were no fields, or there are some non-Matrix fields, add the normal relation condition. (Basically,
        // run this code if the rel criteria wasn't exclusively for Matrix.)
        if (empty($relCriteria['field']) || !empty($relationFieldIds)) {
            if ($dir === self::DIR_FORWARD) {
                $this->_relateSourcesCount++;
                $relTableAlias = 'sources'.$this->_relateSourcesCount;
                $relConditionColumn = 'sourceId';
                $relElementColumn = 'targetId';
            } else {
                $this->_relateTargetsCount++;
                $relTableAlias = 'targets'.$this->_relateTargetsCount;
                $relConditionColumn = 'targetId';
                $relElementColumn = 'sourceId';
            }

            $subQuery = (new Query())
                ->select([$relTableAlias.'.'.$relElementColumn])
                ->from([$relTableAlias => '{{%relations}}'])
                ->where(['in', $relTableAlias.'.'.$relConditionColumn, $relElementIds]);

            if ($relCriteria['sourceSite']) {
                $subQuery->andWhere([
                    'or',
                    [$relTableAlias.'.sourceSiteId' => null],
                    [$relTableAlias.'.sourceSiteId' => $relCriteria['sourceSite']]
                ]);
            }

            if (!empty($relationFieldIds)) {
                $subQuery->andWhere(['in', $relTableAlias.'.fieldId', $relationFieldIds]);
            }

            $conditions[] = ['in', 'elements.id', $subQuery];
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
     * @param array|null &$fieldHandleParts
     * @return FieldInterface|null
     */
    private function _getField($field, array &$fieldHandleParts = null)
    {
        if (is_numeric($field)) {
            $fieldHandleParts = null;
            $fieldModel = Craft::$app->getFields()->getFieldById($field);
        } else {
            $fieldHandleParts = explode('.', $field);
            $fieldModel = Craft::$app->getFields()->getFieldByHandle($fieldHandleParts[0]);
        }

        return $fieldModel;
    }
}
