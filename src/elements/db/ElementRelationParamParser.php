<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\elements\db;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\db\Query;
use craft\fields\Matrix;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\models\Site;

/**
 * Parses a relatedTo param on an ElementQuery.
 *
 * @property bool $isRelationFieldQuery Whether the relatedTo value appears to be for selecting the targets of a single relation field
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ElementRelationParamParser
{
    // Properties
    // =========================================================================

    /**
     * @var int
     */
    private $_joinSourceMatrixBlocksCount = 0;

    /**
     * @var int
     */
    private $_joinTargetMatrixBlocksCount = 0;

    /**
     * @var int
     */
    private $_joinSourcesCount = 0;

    /**
     * @var int
     */
    private $_joinTargetsCount = 0;

    // Public Methods
    // =========================================================================

    /**
     * Parses a relatedTo criteria param and returns the condition(s) or 'false' if there's an issue.
     *
     * @param mixed $relatedTo
     * @param Query $query
     *
     * @return mixed
     */
    public function parseRelationParam($relatedTo, Query $query)
    {
        // Ensure the criteria is an array
        if (is_string($relatedTo)) {
            $relatedTo = ArrayHelper::toArray($relatedTo);
        } else if (!is_array($relatedTo)) {
            $relatedTo = [$relatedTo];
        }

        if (isset($relatedTo['element']) || isset($relatedTo['sourceElement']) || isset($relatedTo['targetElement'])) {
            $relatedTo = [$relatedTo];
        }

        $conditions = [];

        if ($relatedTo[0] === 'and' || $relatedTo[0] === 'or') {
            $glue = array_shift($relatedTo);
        } else {
            $glue = 'or';
        }

        if ($glue === 'or') {
            // Group all of the unspecified elements, so we avoid adding massive JOINs to the query
            $unspecifiedElements = [];

            foreach ($relatedTo as $i => $relCriteria) {
                if (!is_array($relCriteria)) {
                    $unspecifiedElements[] = $relCriteria;
                    unset($relatedTo[$i]);
                }
            }

            if (!empty($unspecifiedElements)) {
                $relatedTo[] = ['element' => $unspecifiedElements];
            }
        }

        foreach ($relatedTo as $relCriteria) {
            $condition = $this->_subparseRelationParam($relCriteria, $query);

            if ($condition) {
                $conditions[] = $condition;
            } else if ($glue === 'or') {
                continue;
            } else {
                return false;
            }
        }

        if (!empty($conditions)) {
            if (count($conditions) === 1) {
                return $conditions[0];
            }

            array_unshift($conditions, $glue);

            return $conditions;
        }

        return false;
    }

    /**
     * Returns whether the relatedTo value appears to be for selecting the targets of a single relation field.
     *
     * @return bool
     */
    public function getIsRelationFieldQuery(): bool
    {
        return (
            $this->_joinSourcesCount === 1 &&
            $this->_joinTargetsCount === 0 &&
            $this->_joinSourceMatrixBlocksCount === 0 &&
            $this->_joinTargetMatrixBlocksCount === 0
        );
    }

    // Private Methods
    // =========================================================================

    /**
     * Parses a part of a relatedTo criteria param and returns the condition or 'false' if there's an issue.
     *
     * @param mixed $relCriteria
     * @param Query $query
     *
     * @return mixed
     */
    private function _subparseRelationParam($relCriteria, Query $query)
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
                $elements = ArrayHelper::toArray($relCriteria[$elementParam], [], false);

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

            return $this->parseRelationParam([
                'or',
                [
                    'sourceElement' => $relElementIds,
                    'field' => $relCriteria['field']
                ],
                [
                    'targetElement' => $relElementIds,
                    'field' => $relCriteria['field']
                ]
            ], $query);
        }

        // Do we need to check for *all* of the element IDs?
        if ($glue === 'and') {
            // Spread it across multiple relation sub-params
            $newRelatedToParam = ['and'];

            foreach ($relElementIds as $elementId) {
                $newRelatedToParam[] = [$elementParam => [$elementId]];
            }

            return $this->parseRelationParam($newRelatedToParam, $query);
        }

        $conditions = [];
        $normalFieldIds = [];

        if ($relCriteria['field']) {
            // Loop through all of the fields in this rel criteria, create the Matrix-specific conditions right away
            // and save the normal field IDs for later
            $fields = ArrayHelper::toArray($relCriteria['field']);

            foreach ($fields as $field) {

                if (is_numeric($field)) {
                    $fieldHandleParts = null;
                    $fieldModel = Craft::$app->getFields()->getFieldById($field);
                } else {
                    $fieldHandleParts = explode('.', $field);
                    $fieldModel = Craft::$app->getFields()->getFieldByHandle($fieldHandleParts[0]);
                }

                if (!$fieldModel) {
                    continue;
                }

                /** @var Field $fieldModel */
                // Is this a Matrix field?
                if (get_class($fieldModel) == Matrix::class) {
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

                    if (isset($relCriteria['sourceElement'])) {
                        $this->_joinSourcesCount++;
                        $this->_joinTargetMatrixBlocksCount++;

                        $sourcesAlias = 'sources'.$this->_joinSourcesCount;
                        $targetMatrixBlocksAlias = 'target_matrixblocks'.$this->_joinTargetMatrixBlocksCount;

                        $relationsJoinConditions = [
                            'and',
                            "[[{$sourcesAlias}.targetId]] = [[elements.id]]"
                        ];

                        if ($relCriteria['sourceSite']) {

                            $relationsJoinConditions[] = [
                                'or',
                                [$sourcesAlias.'.sourceSiteId' => null],
                                [$sourcesAlias.'.sourceSiteId' => $relCriteria['sourceSite']]
                            ];
                        }

                        $query->leftJoin("{{%relations}} {$sourcesAlias}", $relationsJoinConditions);
                        $query->leftJoin("{{%matrixblocks}} {$targetMatrixBlocksAlias}", "[[{$targetMatrixBlocksAlias}.id]] = [[{$sourcesAlias}.sourceId]]");

                        $condition = [
                            'and',
                            Db::parseParam($targetMatrixBlocksAlias.'.ownerId', $relElementIds),
                            [$targetMatrixBlocksAlias.'.fieldId' => $fieldModel->id]
                        ];

                        if (!empty($blockTypeFieldIds)) {
                            $condition[] = Db::parseParam($sourcesAlias.'.fieldId', $blockTypeFieldIds);
                        }
                    } else {
                        $this->_joinSourceMatrixBlocksCount++;
                        $sourceMatrixBlocksAlias = 'source_matrixblocks'.$this->_joinSourceMatrixBlocksCount;
                        $matrixBlockTargetsAlias = 'matrixblock_targets'.$this->_joinSourceMatrixBlocksCount;

                        $relationsJoinConditions = [
                            'and',
                            "[[{$matrixBlockTargetsAlias}.sourceId]] = [[{$sourceMatrixBlocksAlias}.id]]"
                        ];

                        if ($relCriteria['sourceSite']) {
                            $relationsJoinConditions[] = [
                                'or',
                                [$matrixBlockTargetsAlias.'.sourceSiteId' => null],
                                [$matrixBlockTargetsAlias.'.sourceSiteId' => $relCriteria['sourceSite']]
                            ];
                        }

                        $query->leftJoin("{{%matrixblocks}} {$sourceMatrixBlocksAlias}", "[[{$sourceMatrixBlocksAlias}.ownerId]] = [[elements.id]]");
                        $query->leftJoin("{{%relations}} {$matrixBlockTargetsAlias}", $relationsJoinConditions);

                        $condition = [
                            'and',
                            Db::parseParam($matrixBlockTargetsAlias.'.targetId', $relElementIds),
                            [$sourceMatrixBlocksAlias.'.fieldId' => $fieldModel->id]
                        ];

                        if (!empty($blockTypeFieldIds)) {
                            $condition[] = Db::parseParam($matrixBlockTargetsAlias.'.fieldId', $blockTypeFieldIds);
                        }
                    }

                    $conditions[] = $condition;
                } else {
                    $normalFieldIds[] = $fieldModel->id;
                }
            }
        }

        // If there were no fields, or there are some non-Matrix fields, add the normal relation condition. (Basically,
        // run this code if the rel criteria wasn't exclusively for Matrix.)
        if (empty($relCriteria['field']) || !empty($normalFieldIds)) {
            if (isset($relCriteria['sourceElement'])) {
                $this->_joinSourcesCount++;
                $relTableAlias = 'sources'.$this->_joinSourcesCount;
                $relConditionColumn = 'sourceId';
                $relElementColumn = 'targetId';
            } else {
                // $relCriteria['targetElement'], then
                $this->_joinTargetsCount++;
                $relTableAlias = 'targets'.$this->_joinTargetsCount;
                $relConditionColumn = 'targetId';
                $relElementColumn = 'sourceId';
            }

            $relationsJoinConditions = [
                'and',
                "[[{$relTableAlias}.{$relElementColumn}]] = [[elements.id]]"
            ];

            if ($relCriteria['sourceSite']) {
                $relationsJoinConditions[] = [
                    'or',
                    [$relTableAlias.'.sourceSiteId' => null],
                    [$relTableAlias.'.sourceSiteId' => $relCriteria['sourceSite']]
                ];
            }

            $query->leftJoin("{{%relations}} {$relTableAlias}", $relationsJoinConditions);
            $condition = Db::parseParam($relTableAlias.'.'.$relConditionColumn, $relElementIds);

            if (!empty($normalFieldIds)) {
                $condition = [
                    'and',
                    $condition,
                    Db::parseParam($relTableAlias.'.fieldId', $normalFieldIds)
                ];
            }

            $conditions[] = $condition;
        }

        if (!empty($conditions)) {
            if (count($conditions) == 1) {
                return $conditions[0];
            }

            array_unshift($conditions, 'or');

            return $conditions;
        }

        return false;
    }
}
